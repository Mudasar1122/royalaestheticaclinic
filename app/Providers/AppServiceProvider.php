<?php

namespace App\Providers;

use App\Models\FollowUp;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['components.navbar', 'settings.notification'], function ($view): void {
            $now = now('Asia/Karachi');
            $reminderWindowEnd = $now->copy()->addHours(24);

            $followUpNotifications = FollowUp::query()
                ->with(['lead.contact'])
                ->where('status', 'pending')
                ->whereBetween('due_at', [$now, $reminderWindowEnd])
                ->orderBy('due_at')
                ->limit(8)
                ->get()
                ->map(function (FollowUp $followUp): array {
                    $dueAt = $followUp->due_at;
                    $pakistanDueAt = $dueAt?->timezone('Asia/Karachi');
                    $lead = $followUp->lead;
                    $leadName = $lead?->contact?->full_name ?? 'Unnamed lead';
                    $sourceLabel = $lead?->source_platform === 'manual'
                        ? 'Walk In Lead'
                        : ucfirst(str_replace('_', ' ', (string) ($lead?->source_platform ?? 'Unknown source')));
                    $stageLabel = ucfirst(str_replace('_', ' ', (string) $followUp->stage_snapshot));
                    $tab = $pakistanDueAt !== null && $pakistanDueAt->isToday() ? 'today' : 'upcoming';

                    return [
                        'id' => 'follow_up_'.$followUp->id,
                        'title' => 'Follow-up Due Soon',
                        'description' => $leadName.' ('.$stageLabel.') due at '.($pakistanDueAt?->format('d M Y h:i A') ?? '-').' PKT',
                        'from' => $sourceLabel,
                        'received_at' => $pakistanDueAt,
                        'is_highlighted' => true,
                        'lead_id' => $followUp->lead_id,
                        'url' => route('clinicAppointments', ['tab' => $tab]),
                        'category' => 'follow_up',
                    ];
                });

            $webhookNotifications = WebhookEvent::query()
                ->where('platform', 'whatsapp')
                ->where('received_at', '>=', $now->copy()->subHours(24))
                ->latest('received_at')
                ->limit(8)
                ->get()
                ->map(function (WebhookEvent $event): array {
                    $payload = is_array($event->payload) ? $event->payload : [];
                    $crmPayload = is_array($payload['_crm'] ?? null) ? $payload['_crm'] : [];
                    $messageBody = trim((string) (
                        $payload['Body']
                        ?? data_get($payload, 'entry.0.changes.0.value.messages.0.text.body')
                        ?? ''
                    ));
                    $from = (string) (
                        $payload['From']
                        ?? $payload['WaId']
                        ?? data_get($payload, 'entry.0.changes.0.value.contacts.0.wa_id', 'Unknown')
                    );

                    $isHighlighted = $event->status === 'failed'
                        || (bool) ($crmPayload['manual_follow_up_required'] ?? false)
                        || stripos((string) ($event->error_message ?? ''), 'manual') !== false;

                    $title = $isHighlighted
                        ? 'Manual Follow-up Required'
                        : 'WhatsApp Lead Updated';

                    $description = $isHighlighted
                        ? ((string) ($event->error_message ?: 'Automatic follow-up failed. Please add it manually.'))
                        : ($messageBody !== '' ? $messageBody : 'Inbound WhatsApp message synced with CRM.');

                    return [
                        'id' => $event->id,
                        'title' => $title,
                        'description' => $description,
                        'from' => $from,
                        'received_at' => $event->received_at,
                        'is_highlighted' => $isHighlighted,
                        'lead_id' => $crmPayload['lead_id'] ?? null,
                        'url' => route('notification'),
                        'category' => 'whatsapp',
                    ];
                })
                ->filter(static fn (array $notification): bool => (bool) ($notification['is_highlighted'] ?? false))
                ->values();

            $notifications = $followUpNotifications
                ->concat($webhookNotifications)
                ->sortByDesc(static fn (array $notification): int => (int) (($notification['received_at'] ?? now())?->getTimestamp() ?? 0))
                ->take(8)
                ->values();

            $view->with('crmNotifications', $notifications);
            $view->with('crmNotificationCount', $notifications->count());
            $view->with(
                'crmHighlightedNotificationCount',
                $notifications->where('is_highlighted', true)->count()
            );
        });
    }
}
