@php
    $statusMessage = session('status');
@endphp

@if ($statusMessage)
    <div class="royal-status-flash" data-royal-flash role="status" aria-live="polite" data-flash-duration="4200">
        <div class="royal-status-flash__icon">
            <iconify-icon icon="heroicons:check-badge-solid"></iconify-icon>
        </div>
        <p class="royal-status-flash__message">{{ $statusMessage }}</p>
        <button type="button" class="royal-status-flash__close" data-royal-flash-close aria-label="Dismiss message">
            <iconify-icon icon="heroicons:x-mark"></iconify-icon>
        </button>
        <span class="royal-status-flash__progress" aria-hidden="true"></span>
    </div>
@endif
