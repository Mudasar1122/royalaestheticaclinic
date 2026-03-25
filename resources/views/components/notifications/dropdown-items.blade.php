@foreach ($notifications as $notification)
    <div data-navbar-notification-item class="flex px-4 py-3 {{ !empty($notification['is_highlighted']) ? 'bg-danger-50 dark:bg-danger-600/10 border-l-4 border-danger-500' : '' }} justify-between gap-2">
        <a href="{{ $notification['url'] ?? route('notification') }}" class="flex items-center gap-3 min-w-0 flex-1 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg px-2 py-1">
            <div class="flex-shrink-0 relative w-11 h-11 {{ !empty($notification['is_highlighted']) ? 'bg-danger-100 dark:bg-danger-600/25 text-danger-600' : 'bg-success-200 dark:bg-success-600/25 text-success-600' }} flex justify-center items-center rounded-full">
                <iconify-icon icon="{{ !empty($notification['is_highlighted']) ? 'heroicons:exclamation-triangle' : 'heroicons:chat-bubble-left-right' }}" class="text-xl"></iconify-icon>
            </div>
            <div class="min-w-0">
                <h6 class="text-sm fw-semibold mb-1 line-clamp-1">{{ $notification['title'] ?? 'Notification' }}</h6>
                <p class="mb-0 text-sm line-clamp-2">{{ $notification['description'] ?? '' }}</p>
                <p class="mb-0 text-xs text-secondary-light line-clamp-1">{{ $notification['from'] ?? '' }}</p>
            </div>
        </a>
        <div class="shrink-0 text-end flex flex-col items-end gap-2">
            <span class="text-xs text-neutral-500">{{ ($notification['received_at'] ?? null)?->diffForHumans() }}</span>
        </div>
    </div>
@endforeach
