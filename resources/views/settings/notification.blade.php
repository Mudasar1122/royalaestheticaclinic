@extends('layout.layout')

@php
    $title = 'Notification';
    $subTitle = 'Settings - Notification';
    $notifications = isset($crmNotifications) ? collect($crmNotifications) : collect();
    $notificationCount = $crmNotificationCount ?? $notifications->count();
    $highlightedCount = $crmHighlightedNotificationCount ?? 0;
    $script = <<<'SCRIPT'
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-notification-url]').forEach(function (row) {
        var url = row.getAttribute('data-notification-url');

        if (!url) {
            return;
        }

        row.addEventListener('click', function (event) {
            if (event.target.closest('a, button, input, select, textarea, label, summary')) {
                return;
            }

            window.location.href = url;
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            window.location.href = url;
        });
    });
});
</script>
SCRIPT;
@endphp

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Total Notifications</p>
                <h5 class="mb-0 dark:text-white">{{ number_format($notificationCount) }}</h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Highlighted Alerts</p>
                <h5 class="mb-0 {{ $highlightedCount > 0 ? 'text-danger-600' : 'dark:text-white' }}">
                    {{ number_format($highlightedCount) }}
                </h5>
            </div>
        </div>
        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none">
            <div class="card-body p-5">
                <p class="text-secondary-light mb-1">Status</p>
                <h5 class="mb-0 dark:text-white">{{ $highlightedCount > 0 ? 'Action Needed' : 'All Clear' }}</h5>
            </div>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">CRM Notifications</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Alert</th>
                            <th>From</th>
                            <th>Lead</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notifications as $notification)
                            @php
                                $notificationUrl = $notification['url'] ?? null;
                            @endphp
                            <tr
                                class="{{ !empty($notification['is_highlighted']) ? 'bg-danger-50 dark:bg-danger-600/10' : '' }} {{ $notificationUrl ? 'cursor-pointer transition hover:bg-primary-50 dark:hover:bg-primary-600/10 focus:outline-none focus:ring-2 focus:ring-primary-500/30' : '' }}"
                                @if ($notificationUrl)
                                    data-notification-url="{{ $notificationUrl }}"
                                    tabindex="0"
                                    role="link"
                                    aria-label="Open {{ $notification['title'] ?? 'notification' }}"
                                @endif
                            >
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium {{ !empty($notification['is_highlighted']) ? 'text-danger-700 dark:text-danger-300' : 'text-neutral-700 dark:text-neutral-100' }}">
                                            {{ $notification['title'] ?? 'Notification' }}
                                        </span>
                                        <span class="text-xs text-secondary-light">{{ $notification['description'] ?? '' }}</span>
                                    </div>
                                </td>
                                <td>{{ $notification['from'] ?? '-' }}</td>
                                <td>
                                    @if (!empty($notification['lead_id']))
                                        #{{ $notification['lead_id'] }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ ($notification['received_at'] ?? null)?->format('d M Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-10 text-secondary-light">No notifications available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
