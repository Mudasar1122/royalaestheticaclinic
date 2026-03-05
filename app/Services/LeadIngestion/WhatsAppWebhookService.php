<?php

namespace App\Services\LeadIngestion;

use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsAppWebhookService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function ingest(array $payload): array
    {
        $message = $this->extractFirstMessage($payload);

        if ($message === null) {
            return [
                'status' => 'ignored',
                'reason' => 'No inbound WhatsApp message found in payload.',
            ];
        }

        $eventId = $this->resolveEventId($payload, $message);
        $eventType = (string) data_get($message, 'type', 'unknown');

        $webhookEvent = $this->findOrPrepareWebhookEvent($eventId, $payload, $eventType);

        if ($webhookEvent->status === 'processed') {
            return [
                'status' => 'duplicate',
                'event_id' => $eventId,
            ];
        }

        try {
            $result = DB::transaction(function () use ($payload, $message): array {
                [$contact, $lead, $leadCreated] = $this->resolveLeadFromMessage($payload, $message);

                $activity = LeadActivity::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'platform' => 'whatsapp',
                    'activity_type' => 'message_received',
                    'direction' => 'inbound',
                    'platform_message_id' => (string) data_get($message, 'id', ''),
                    'message_text' => $this->extractMessageBody($message),
                    'payload' => $message,
                    'happened_at' => $this->resolveHappenedAt($message),
                ]);

                $followUp = FollowUp::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'trigger_type' => $leadCreated ? 'auto_first_message' : 'auto_inbound_message',
                    'stage_snapshot' => $lead->stage,
                    'status' => 'pending',
                    'due_at' => now()->addMinutes($this->followUpMinutes()),
                    'summary' => $leadCreated
                        ? 'Auto follow-up from first inbound WhatsApp message'
                        : 'Auto follow-up from new inbound WhatsApp message',
                    'metadata' => [
                        'platform' => 'whatsapp',
                        'platform_message_id' => $activity->platform_message_id,
                    ],
                ]);

                $lead->forceFill([
                    'last_activity_at' => $activity->happened_at,
                    'first_message_at' => $lead->first_message_at ?? $activity->happened_at,
                ])->save();

                return [
                    'contact_id' => $contact->id,
                    'lead_id' => $lead->id,
                    'lead_stage' => $lead->stage,
                    'lead_status' => $lead->status,
                    'lead_created' => $leadCreated,
                    'activity_id' => $activity->id,
                    'follow_up_id' => $followUp->id,
                    'follow_up_due_at' => $followUp->due_at?->toISOString(),
                ];
            });

            $webhookEvent->forceFill([
                'status' => 'processed',
                'error_message' => null,
                'processed_at' => now(),
            ])->save();

            return array_merge([
                'status' => 'processed',
                'event_id' => $eventId,
            ], $result);
        } catch (Throwable $exception) {
            $webhookEvent->forceFill([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'processed_at' => null,
            ])->save();

            report($exception);

            return [
                'status' => 'failed',
                'event_id' => $eventId,
                'message' => 'Webhook processing failed.',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     * @return array{0: Contact, 1: Lead, 2: bool}
     */
    private function resolveLeadFromMessage(array $payload, array $message): array
    {
        $contact = $this->resolveContact($payload, $message);

        $openLead = Lead::query()
            ->where('contact_id', $contact->id)
            ->where('status', 'open')
            ->latest('updated_at')
            ->first();

        if ($openLead !== null) {
            return [$contact, $openLead, false];
        }

        $recentClosedLead = Lead::query()
            ->where('contact_id', $contact->id)
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->first();

        if ($recentClosedLead !== null && $recentClosedLead->closed_at !== null) {
            $reopenLimit = now()->subDays($this->reopenWindowDays());

            if ($recentClosedLead->closed_at->greaterThanOrEqualTo($reopenLimit)) {
                $recentClosedLead->forceFill([
                    'status' => 'open',
                    'stage' => $this->reopenStage(),
                    'closed_at' => null,
                    'last_activity_at' => now(),
                ])->save();

                return [$contact, $recentClosedLead, false];
            }
        }

        $lead = Lead::create([
            'contact_id' => $contact->id,
            'source_platform' => 'whatsapp',
            'status' => 'open',
            'stage' => 'initial',
            'first_message_at' => now(),
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'whatsapp_webhook',
            ],
        ]);

        return [$contact, $lead, true];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     */
    private function resolveEventId(array $payload, array $message): string
    {
        $platformMessageId = (string) data_get($message, 'id', '');

        if ($platformMessageId !== '') {
            return $platformMessageId;
        }

        $serializedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return sha1($serializedPayload !== false ? $serializedPayload : serialize($payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  string  $eventId
     * @param  string  $eventType
     */
    private function findOrPrepareWebhookEvent(string $eventId, array $payload, string $eventType): WebhookEvent
    {
        $event = WebhookEvent::query()
            ->where('platform', 'whatsapp')
            ->where('event_id', $eventId)
            ->first();

        if ($event === null) {
            return WebhookEvent::create([
                'platform' => 'whatsapp',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => 'received',
                'received_at' => now(),
            ]);
        }

        if ($event->status === 'processed') {
            return $event;
        }

        $event->forceFill([
            'event_type' => $eventType,
            'payload' => $payload,
            'status' => 'received',
            'error_message' => null,
            'processed_at' => null,
            'received_at' => now(),
        ])->save();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     */
    private function resolveContact(array $payload, array $message): Contact
    {
        $waId = (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.wa_id', '');
        $fallbackFrom = (string) data_get($message, 'from', '');
        $externalId = $waId !== '' ? $waId : $fallbackFrom;
        $normalizedPhone = $this->normalizePhone($externalId);
        $displayName = (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.profile.name', '');

        $identity = $externalId !== ''
            ? ContactIdentity::query()
                ->where('platform', 'whatsapp')
                ->where('external_id', $externalId)
                ->first()
            : null;

        $contact = $identity?->contact;

        if ($contact === null && $normalizedPhone !== null) {
            $contact = Contact::query()
                ->where('normalized_phone', $normalizedPhone)
                ->first();
        }

        if ($contact === null) {
            $contact = Contact::create([
                'full_name' => $displayName !== '' ? $displayName : null,
                'phone' => $externalId !== '' ? $externalId : null,
                'normalized_phone' => $normalizedPhone,
                'default_source' => 'whatsapp',
                'metadata' => [
                    'created_from' => 'whatsapp_webhook',
                ],
            ]);
        } else {
            $contact->forceFill([
                'full_name' => $contact->full_name ?: ($displayName !== '' ? $displayName : null),
                'phone' => $contact->phone ?: ($externalId !== '' ? $externalId : null),
                'normalized_phone' => $contact->normalized_phone ?: $normalizedPhone,
            ])->save();
        }

        if ($externalId !== '') {
            ContactIdentity::updateOrCreate(
                [
                    'platform' => 'whatsapp',
                    'external_id' => $externalId,
                ],
                [
                    'contact_id' => $contact->id,
                    'display_name' => $displayName !== '' ? $displayName : null,
                    'raw_payload' => data_get($payload, 'entry.0.changes.0.value.contacts.0', []),
                    'last_seen_at' => now(),
                ]
            );
        }

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function resolveHappenedAt(array $message): Carbon
    {
        $timestamp = (int) data_get($message, 'timestamp', 0);

        if ($timestamp <= 0) {
            return now();
        }

        return Carbon::createFromTimestampUTC($timestamp)->setTimezone(config('app.timezone'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function extractFirstMessage(array $payload): ?array
    {
        $messages = data_get($payload, 'entry.0.changes.0.value.messages');

        if (!is_array($messages) || !isset($messages[0]) || !is_array($messages[0])) {
            return null;
        }

        return $messages[0];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function extractMessageBody(array $message): string
    {
        $type = (string) data_get($message, 'type', 'text');

        if ($type === 'text') {
            return (string) data_get($message, 'text.body', '');
        }

        if ($type === 'button') {
            return (string) data_get($message, 'button.text', '');
        }

        if ($type === 'interactive') {
            return (string) data_get($message, 'interactive.button_reply.title', data_get($message, 'interactive.list_reply.title', '[interactive message]'));
        }

        return '['.$type.' message]';
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if ($digits === null || $digits === '') {
            return null;
        }

        return '+'.$digits;
    }

    private function followUpMinutes(): int
    {
        return max(1, (int) config('crm.whatsapp.follow_up_minutes', 60));
    }

    private function reopenWindowDays(): int
    {
        return max(0, (int) config('crm.whatsapp.reopen_window_days', 30));
    }

    private function reopenStage(): string
    {
        return (string) config('crm.whatsapp.reopen_stage', 'contacted');
    }
}
