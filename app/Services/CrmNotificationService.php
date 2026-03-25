<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Support\Collection;

class CrmNotificationService
{
    /**
     * @return array<string, mixed>
     */
    public function getDropdownViewData(?User $user, int $perPage = 4, int $page = 1): array
    {
        $notifications = $this->getNotificationsForUser($user);
        $totalCount = $notifications->count();
        $highlightedCount = $notifications->where('is_highlighted', true)->count();
        $currentPage = max(1, $page);
        $resolvedPerPage = max(1, $perPage);
        $pageItems = $notifications->forPage($currentPage, $resolvedPerPage)->values();
        $hasMore = ($currentPage * $resolvedPerPage) < $totalCount;

        return [
            'crmNotifications' => $pageItems,
            'crmNotificationCount' => $totalCount,
            'crmHighlightedNotificationCount' => $highlightedCount,
            'crmNotificationCurrentPage' => $currentPage,
            'crmNotificationPerPage' => $resolvedPerPage,
            'crmNotificationHasMore' => $hasMore,
            'crmNotificationNextPage' => $hasMore ? $currentPage + 1 : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getNotificationPageViewData(?User $user): array
    {
        $notifications = $this->getNotificationsForUser($user);

        return [
            'crmNotifications' => $notifications,
            'crmNotificationCount' => $notifications->count(),
            'crmHighlightedNotificationCount' => $notifications->where('is_highlighted', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDropdownFeedData(?User $user, int $perPage = 4, int $page = 1): array
    {
        $payload = $this->getDropdownViewData($user, $perPage, $page);

        return [
            'notifications' => $payload['crmNotifications'],
            'total_count' => $payload['crmNotificationCount'],
            'highlighted_count' => $payload['crmHighlightedNotificationCount'],
            'current_page' => $payload['crmNotificationCurrentPage'],
            'per_page' => $payload['crmNotificationPerPage'],
            'has_more' => $payload['crmNotificationHasMore'],
            'next_page' => $payload['crmNotificationNextPage'],
        ];
    }

    public function getNotificationsForUser(?User $user): Collection
    {
        if ($user === null) {
            return collect();
        }

        $visibleLeadIds = $user->isAdmin()
            ? null
            : array_fill_keys(
                Lead::query()
                    ->visibleTo($user)
                    ->pluck('id')
                    ->map(static fn ($leadId): string => (string) $leadId)
                    ->all(),
                true
            );
        $pakistanNow = now('Asia/Karachi');
        $now = $pakistanNow->copy()->utc();
        $reminderWindowEnd = $now->copy()->addHours(24);

        $followUpNotifications = FollowUp::query()
            ->visibleTo($user)
            ->with(['lead.contact'])
            ->where('status', 'pending')
            ->whereBetween('due_at', [$now, $reminderWindowEnd])
            ->orderBy('due_at')
            ->get()
            ->map(function (FollowUp $followUp): array {
                $dueAt = $followUp->due_at;
                $pakistanDueAt = $dueAt?->timezone('Asia/Karachi');
                $lead = $followUp->lead;
                $leadName = $lead?->contact?->full_name ?? 'Unnamed lead';
                $sourceLabel = match ((string) ($lead?->source_platform ?? '')) {
                    'manual' => 'Walk In Lead',
                    'meta' => 'Lead Form',
                    'google_business' => 'Google Business',
                    default => ucfirst(str_replace('_', ' ', (string) ($lead?->source_platform ?? 'Unknown source'))),
                };
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
                    'url' => $followUp->lead_id !== null
                        ? route('clinicLeadFollowUp', $followUp->lead_id)
                        : route('clinicAppointments', ['tab' => $tab]),
                    'category' => 'follow_up',
                ];
            });

        $webhookNotifications = WebhookEvent::query()
            ->where('platform', 'whatsapp')
            ->where('received_at', '>=', $now->copy()->subHours(24))
            ->latest('received_at')
            ->get()
            ->map(function (WebhookEvent $event) use ($visibleLeadIds): ?array {
                $payload = is_array($event->payload) ? $event->payload : [];
                $crmPayload = is_array($payload['_crm'] ?? null) ? $payload['_crm'] : [];
                $leadId = !empty($crmPayload['lead_id']) ? (int) $crmPayload['lead_id'] : null;
                $activeLeadExists = $leadId !== null
                    ? (
                        $visibleLeadIds === null
                            ? Lead::query()->whereKey($leadId)->exists()
                            : isset($visibleLeadIds[(string) $leadId])
                    )
                    : false;

                if ($leadId !== null && !$activeLeadExists) {
                    return null;
                }

                if ($leadId === null && $visibleLeadIds !== null) {
                    return null;
                }

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
                    'lead_id' => $leadId,
                    'url' => $leadId !== null
                        ? route('clinicLeadFollowUp', $leadId)
                        : route('notification'),
                    'category' => 'whatsapp',
                ];
            })
            ->filter()
            ->filter(static fn (array $notification): bool => (bool) ($notification['is_highlighted'] ?? false))
            ->values();

        return $followUpNotifications
            ->concat($webhookNotifications)
            ->sortByDesc(static fn (array $notification): int => (int) (($notification['received_at'] ?? now())?->getTimestamp() ?? 0))
            ->values();
    }
}
