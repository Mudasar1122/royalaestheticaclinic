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
        $message = $this->extractInboundMessage($payload);

        if ($message === null) {
            return [
                'status' => 'ignored',
                'reason' => 'No inbound WhatsApp message found in payload.',
            ];
        }

        $eventId = $this->resolveEventId($payload, $message);
        $eventType = (string) data_get($message, 'event_type', data_get($message, 'type', 'unknown'));

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

                $manualFollowUpRequired = false;
                $manualFollowUpReason = null;
                $followUp = null;

                try {
                    $followUp = FollowUp::create([
                        'lead_id' => $lead->id,
                        'contact_id' => $contact->id,
                        'trigger_type' => $leadCreated ? 'auto_first_message' : 'auto_inbound_message',
                        'stage_snapshot' => $this->resolveStageSnapshot($lead, $leadCreated),
                        'status' => 'pending',
                        'due_at' => now(),
                        'summary' => $leadCreated
                            ? 'Auto initial follow-up from inbound WhatsApp message'
                            : 'Auto follow-up from inbound WhatsApp message',
                        'metadata' => [
                            'platform' => 'whatsapp',
                            'platform_message_id' => $activity->platform_message_id,
                            'auto_created' => true,
                        ],
                    ]);
                } catch (Throwable $exception) {
                    report($exception);

                    $manualFollowUpRequired = true;
                    $manualFollowUpReason = 'Auto follow-up could not be created. Please add follow-up manually.';
                }

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
                    'follow_up_id' => $followUp?->id,
                    'follow_up_due_at' => $followUp?->due_at?->toISOString(),
                    'manual_follow_up_required' => $manualFollowUpRequired,
                    'manual_follow_up_reason' => $manualFollowUpReason,
                ];
            });

            $manualFollowUpRequired = (bool) ($result['manual_follow_up_required'] ?? false);

            $eventPayload = $payload;
            $eventPayload['_crm'] = [
                'contact_id' => $result['contact_id'] ?? null,
                'lead_id' => $result['lead_id'] ?? null,
                'follow_up_id' => $result['follow_up_id'] ?? null,
                'manual_follow_up_required' => $manualFollowUpRequired,
            ];

            $webhookEvent->forceFill([
                'payload' => $eventPayload,
                'status' => 'processed',
                'error_message' => $manualFollowUpRequired ? (string) ($result['manual_follow_up_reason'] ?? '') : null,
                'processed_at' => now(),
            ])->save();

            return array_merge([
                'status' => $manualFollowUpRequired ? 'processed_with_warning' : 'processed',
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

        $existingLead = Lead::query()
            ->where('contact_id', $contact->id)
            ->latest('updated_at')
            ->first();

        if ($existingLead !== null) {
            return [$contact, $existingLead, false];
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
        $isTwilio = $this->isTwilioPayload($payload);
        $rawExternalId = $isTwilio
            ? (string) data_get($payload, 'WaId', data_get($payload, 'From', data_get($message, 'from', '')))
            : (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.wa_id', data_get($message, 'from', ''));
        $externalId = $this->normalizeExternalId($rawExternalId);
        $normalizedPhone = $this->normalizePhone($rawExternalId);
        $displayName = $isTwilio
            ? (string) data_get($payload, 'ProfileName', data_get($message, 'profile_name', ''))
            : (string) data_get($payload, 'entry.0.changes.0.value.contacts.0.profile.name', data_get($message, 'profile_name', ''));

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
                'phone' => $normalizedPhone,
                'normalized_phone' => $normalizedPhone,
                'default_source' => 'whatsapp',
                'metadata' => [
                    'created_from' => 'whatsapp_webhook',
                ],
            ]);
        } else {
            $contact->forceFill([
                'full_name' => $contact->full_name ?: ($displayName !== '' ? $displayName : null),
                'phone' => $contact->phone ?: $normalizedPhone,
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
                    'raw_payload' => $isTwilio ? $payload : data_get($payload, 'entry.0.changes.0.value.contacts.0', []),
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
    private function extractInboundMessage(array $payload): ?array
    {
        if ($this->isTwilioPayload($payload)) {
            $from = (string) data_get($payload, 'From', '');
            $messageSid = (string) data_get($payload, 'MessageSid', data_get($payload, 'SmsMessageSid', ''));

            if ($messageSid === '' || !str_starts_with(strtolower($from), 'whatsapp:')) {
                return null;
            }

            $body = trim((string) data_get($payload, 'Body', ''));
            $numMedia = (int) data_get($payload, 'NumMedia', 0);

            if ($body === '' && $numMedia > 0) {
                $body = '[media message]';
            }

            return [
                'id' => $messageSid,
                'type' => $body === '' ? 'unknown' : 'text',
                'from' => (string) data_get($payload, 'WaId', $from),
                'profile_name' => (string) data_get($payload, 'ProfileName', ''),
                'body' => $body,
                'timestamp' => now()->timestamp,
                'provider' => 'twilio',
                'event_type' => 'inbound_message',
                'payload' => $payload,
            ];
        }

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
        $provider = (string) data_get($message, 'provider', '');

        if ($provider === 'twilio') {
            return (string) data_get($message, 'body', '');
        }

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

    private function normalizeExternalId(string $value): string
    {
        $trimmed = trim(str_ireplace('whatsapp:', '', $value));
        $digits = preg_replace('/\D+/', '', $trimmed);

        return $digits === null ? '' : $digits;
    }

    private function resolveStageSnapshot(Lead $lead, bool $leadCreated): string
    {
        if ($leadCreated) {
            return $lead->stage;
        }

        $previousFollowUp = FollowUp::query()
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->first();

        if ($previousFollowUp !== null && trim((string) $previousFollowUp->stage_snapshot) !== '') {
            return $previousFollowUp->stage_snapshot;
        }

        return $lead->stage;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isTwilioPayload(array $payload): bool
    {
        return isset($payload['MessageSid']) || isset($payload['SmsMessageSid']) || isset($payload['WaId']);
    }
}
