@php
    $canSearchLeads = auth()->user()?->hasModulePermission('lead_management', 'view_leads') ?? false;
    $navbarSearchValue = $canSearchLeads && request()->routeIs('clinicLeads')
        ? trim((string) request('search', ''))
        : '';
    $isImpersonating = session()->has('impersonator_id');
@endphp

<div class="navbar-header border-b border-neutral-200 dark:border-neutral-600">
    <div class="flex items-center justify-between">
        <div class="col-auto">
            <div class="flex flex-wrap items-center gap-[16px]">
                <button type="button" class="sidebar-toggle">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon non-active"></iconify-icon>
                    <iconify-icon icon="iconoir:arrow-right" class="icon active"></iconify-icon>
                </button>
                <button type="button" class="sidebar-mobile-toggle d-flex !leading-[0]">
                    <iconify-icon icon="heroicons:bars-3-solid" class="icon !text-[30px]"></iconify-icon>
                </button>
                @if ($canSearchLeads)
                    <form class="navbar-search" method="GET" action="{{ route('clinicLeads') }}">
                        <input type="hidden" name="tab" value="all">
                        <input type="text" name="search" value="{{ $navbarSearchValue }}" placeholder="Search lead by name or phone">
                        <button type="submit" class="navbar-search__submit" aria-label="Search leads">
                            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                        </button>
                    </form>
                @else
                    <div class="navbar-search navbar-search--disabled" aria-hidden="true">
                        <input type="text" value="" placeholder="Search lead by name or phone" disabled>
                        <span class="navbar-search__submit">
                            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
                        </span>
                    </div>
                @endif

            </div>
        </div>
        <div class="col-auto">
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="theme-toggle" class="w-10 h-10 bg-neutral-200 dark:bg-neutral-700 dark:text-white rounded-full flex justify-center items-center">
                    <span id="theme-toggle-dark-icon" class="hidden">
                        <i class="ri-sun-line"></i>
                    </span>
                    <span id="theme-toggle-light-icon" class="hidden">
                        <i class="ri-moon-line"></i>
                    </span>
                </button>

                <!-- Notification Start  -->
                @php
                    $crmNotifications = isset($crmNotifications) ? collect($crmNotifications) : collect();
                    $crmNotificationCount = $crmNotificationCount ?? $crmNotifications->count();
                    $crmHighlightedNotificationCount = $crmHighlightedNotificationCount ?? 0;
                @endphp
                <button
                    data-dropdown-toggle="dropdownNotification"
                    class="relative w-10 h-10 bg-neutral-200 dark:bg-neutral-700 rounded-full flex justify-center items-center"
                    type="button"
                >
                    <iconify-icon icon="iconoir:bell" class="text-neutral-900 dark:text-white text-xl"></iconify-icon>
                    @if ($crmNotificationCount > 0)
                        <span class="absolute top-0 right-0 translate-x-1/3 -translate-y-1/3 min-w-[20px] h-5 px-1 rounded-full border-2 border-white dark:border-neutral-700 bg-danger-600 text-white text-[11px] font-semibold leading-none flex items-center justify-center shadow-none" style="box-shadow: none;">
                            {{ $crmNotificationCount > 99 ? '99+' : $crmNotificationCount }}
                        </span>
                    @endif
                </button>
                <div id="dropdownNotification" class="z-10 hidden bg-white dark:bg-neutral-700 rounded-2xl overflow-hidden shadow-lg max-w-[394px] w-full">
                    <div class="py-3 px-4 rounded-lg bg-primary-50 dark:bg-primary-600/25 m-4 flex items-center justify-between gap-2">
                        <h6 class="text-lg text-neutral-900 font-semibold mb-0">Notification</h6>
                        <span class="w-10 h-10 bg-white dark:bg-neutral-600 {{ $crmHighlightedNotificationCount > 0 ? 'text-danger-600' : 'text-primary-600 dark:text-white' }} font-bold flex justify-center items-center rounded-full">
                            {{ $crmNotificationCount }}
                        </span>
                    </div>
                    <div class="scroll-sm !border-t-0">
                        <div class="max-h-[400px] overflow-y-auto">
                            @forelse ($crmNotifications as $notification)
                                <div class="flex px-4 py-3 {{ !empty($notification['is_highlighted']) ? 'bg-danger-50 dark:bg-danger-600/10 border-l-4 border-danger-500' : '' }} justify-between gap-2">
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
                            @empty
                                <div class="px-4 py-8 text-center text-sm text-secondary-light">
                                    No CRM notifications yet.
                                </div>
                            @endforelse
                        </div>

                        <div class="text-center py-3 px-4">
                            <a href="{{ route('notification') }}" class="text-primary-600 dark:text-primary-600 font-semibold hover:underline text-center">See All Notification </a>
                        </div>
                    </div>
                </div>
                <!-- Notification End  -->


                <button data-dropdown-toggle="dropdownProfile" class="flex justify-center items-center rounded-full" type="button">
                    @if (auth()->user()?->profile_photo_path)
                        <img src="{{ asset('storage/'.auth()->user()->profile_photo_path) }}" alt="image" class="w-10 h-10 object-fit-cover rounded-full">
                    @else
                        <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-600/25 text-primary-600 flex justify-center items-center font-semibold">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </div>
                    @endif
                </button>
                <div id="dropdownProfile" class="z-10 hidden bg-white dark:bg-neutral-700 rounded-lg shadow-lg dropdown-menu-sm p-3">
                    <div class="py-3 px-4 rounded-lg bg-primary-50 dark:bg-primary-600/25 mb-4 flex items-center justify-between gap-2">
                        <div>
                            <h6 class="text-lg text-neutral-900 font-semibold mb-0">{{ auth()->user()?->name ?? 'User' }}</h6>
                            <span class="text-neutral-500">{{ ucfirst(auth()->user()?->role ?? 'staff') }}</span>
                            @if ($isImpersonating)
                                <p class="mb-0 mt-1 text-xs font-medium text-primary-600">Viewing as another user</p>
                            @endif
                        </div>
                        <button type="button" class="hover:text-danger-600">
                            <iconify-icon icon="radix-icons:cross-1" class="icon text-xl"></iconify-icon>
                        </button>
                    </div>

                    <div class="max-h-[400px] overflow-y-auto scroll-sm pe-2">
                        <ul class="flex flex-col">
                            <li>
                                <a class="text-black px-0 py-2 hover:text-primary-600 flex items-center gap-4" href="{{ route('viewProfile') }}">
                                    <iconify-icon icon="solar:user-linear" class="icon text-xl"></iconify-icon>  My Profile
                                </a>
                            </li>
                            @if (auth()->user()?->hasModulePermission('campaign_management', 'view_campaigns'))
                                <li>
                                    <a class="text-black px-0 py-2 hover:text-primary-600 flex items-center gap-4" href="{{ route('email') }}">
                                        <iconify-icon icon="tabler:message-check" class="icon text-xl"></iconify-icon>  Inbox
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a class="text-black px-0 py-2 hover:text-primary-600 flex items-center gap-4" href="{{ route('company') }}">
                                    <iconify-icon icon="icon-park-outline:setting-two" class="icon text-xl"></iconify-icon>  Setting
                                </a>
                            </li>
                            @if ($isImpersonating)
                                <li>
                                    <form action="{{ route('usersStopImpersonation') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left text-black px-0 py-2 hover:text-primary-600 flex items-center gap-4">
                                            <iconify-icon icon="solar:shield-user-linear" class="icon text-xl"></iconify-icon> Back to Admin
                                        </button>
                                    </form>
                                </li>
                            @endif
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left text-black px-0 py-2 hover:text-danger-600 flex items-center gap-4">
                                        <iconify-icon icon="lucide:power" class="icon text-xl"></iconify-icon> Log Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
