@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Leads';

    $stageBadgeClasses = [
        'new' => 'bg-warning-100 dark:bg-warning-600/20 text-warning-600 dark:text-warning-300',
        'contacted' => 'bg-info-100 dark:bg-info-600/20 text-info-600 dark:text-info-300',
        'visit' => 'bg-primary-100 dark:bg-primary-600/20 text-primary-600 dark:text-primary-300',
        'negotiation' => 'bg-purple-100 dark:bg-purple-600/20 text-purple-600 dark:text-purple-300',
        'booked' => 'bg-success-100 dark:bg-success-600/20 text-success-600 dark:text-success-300',
    ];

    $normalizeStage = static fn (string $stage): string => match ($stage) {
        'initial' => 'new',
        'proposal' => 'negotiation',
        'confirmed' => 'booked',
        default => $stage,
    };

    $readableStage = static function (string $stage) use ($normalizeStage, $stages): string {
        $normalized = $normalizeStage($stage);
        return $stages[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    };
@endphp

@section('content')
    <div class="card border-0">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 p-4">
            <div class="mb-3" style="display:flex; flex-wrap:wrap; gap:8px;">
                @foreach ($leadTabs as $tabKey => $tabConfig)
                    @php
                        $tabQuery = array_filter(
                            [
                                'tab' => $tabKey,
                                'search' => $filters['search'] !== '' ? $filters['search'] : null,
                                'source' => $filters['source'] !== '' ? $filters['source'] : null,
                                'status' => $filters['status'] !== '' ? $filters['status'] : null,
                            ],
                            static fn ($value) => $value !== null && $value !== ''
                        );
                    @endphp
                    <a
                        href="{{ route('clinicLeads', $tabQuery) }}"
                        class="rounded-lg border px-3 py-2 text-sm font-medium"
                        style="{{ $activeTab === $tabKey ? 'border-color:#c88a00; background:#fff7e6; color:#b77900;' : 'border-color:#d4d7dd; color:#4b5563; background:#fff;' }}"
                    >
                        {{ $tabConfig['label'] }} ({{ number_format($tabCounts[$tabKey] ?? 0) }})
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('clinicLeads') }}" class="w-100" style="display:grid; grid-template-columns:minmax(260px,1fr) 220px 200px auto; gap:12px; align-items:end;">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div style="min-width:0;">
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control rounded-lg" placeholder="Search name, phone, email">
                </div>
                <div style="width:220px;">
                    <select name="source" class="form-select rounded-lg">
                        <option value="">All Sources</option>
                        @foreach ($sources as $sourceKey => $sourceLabel)
                            <option value="{{ $sourceKey }}" @selected($filters['source'] === $sourceKey)>{{ $sourceLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="width:200px;">
                    <select name="status" class="form-select rounded-lg">
                        <option value="">All Status</option>
                        <option value="open" @selected($filters['status'] === 'open')>Open</option>
                        <option value="closed" @selected($filters['status'] === 'closed')>Closed</option>
                    </select>
                </div>
                <div style="white-space:nowrap;">
                    <div style="display:flex; gap:8px; flex-wrap:nowrap;">
                        <button type="submit" class="btn btn-primary text-sm btn-sm px-3 py-2 rounded-lg">Apply Filter</button>
                        <a href="{{ route('clinicLeads', ['tab' => $activeTab]) }}" class="btn btn-primary text-sm btn-sm px-3 py-2 rounded-lg">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div>
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Source</th>
                            <th>Stage</th>
                            <th>Next Follow-up</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $nextFollowUp = $lead->followUps->first();
                                $normalizedStage = $normalizeStage((string) $lead->stage);
                                $stageLabel = $readableStage((string) $lead->stage);
                            @endphp
                            <tr>
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-medium text-neutral-700 dark:text-neutral-100">{{ $lead->contact?->full_name ?? 'Unnamed Lead' }}</span>
                                        <span class="text-xs text-secondary-light">{{ $lead->contact?->phone ?? $lead->contact?->email ?? 'No contact info' }}</span>
                                    </div>
                                </td>
                                <td>{{ ucfirst(str_replace('_', ' ', $lead->source_platform)) }}</td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $stageBadgeClasses[$normalizedStage] ?? 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200' }}">
                                        {{ $stageLabel }}
                                    </span>
                                </td>
                                <td>
                                    @if ($nextFollowUp)
                                        <span class="{{ $nextFollowUp->due_at < now() ? 'text-danger-600 font-medium' : '' }}">
                                            {{ $nextFollowUp->due_at?->format('d M Y h:i A') }}
                                        </span>
                                    @else
                                        <span class="text-secondary-light">No pending follow-up</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $lead->status === 'open' ? 'bg-success-100 dark:bg-success-600/25 text-success-700 dark:text-success-300' : 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200' }}">
                                        {{ ucfirst($lead->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="relative inline-block text-left" data-action-dropdown style="position: relative; display: inline-block; z-index: 20;">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                                            data-action-dropdown-button
                                            aria-expanded="false"
                                            aria-controls="lead-action-menu-{{ $lead->id }}"
                                        >
                                            Action
                                            <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                                        </button>
                                        <div
                                            id="lead-action-menu-{{ $lead->id }}"
                                            class="hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                                            style="top: calc(100% + 8px); width: 250px; max-height: 320px; overflow-y: auto; z-index: 9999;"
                                            data-action-dropdown-menu
                                        >
                                            <button
                                                type="button"
                                                class="w-full text-start rounded text-secondary-light hover:bg-neutral-200 text-hover-neutral-900"
                                                style="padding: 8px 12px; font-size: 15px;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#followUpModal-{{ $lead->id }}"
                                                data-action-menu-close
                                            >
                                                Add Follow-up
                                            </button>
                                            <button
                                                type="button"
                                                class="w-full text-start rounded text-secondary-light hover:bg-neutral-200 text-hover-neutral-900"
                                                style="padding: 8px 12px; font-size: 15px;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editLeadModal-{{ $lead->id }}"
                                                data-action-menu-close
                                            >
                                                Edit Lead
                                            </button>
                                            <hr class="my-2 border-neutral-200 dark:border-neutral-600">
                                            @foreach ($stages as $stageKey => $stageOptionLabel)
                                                @if ($stageKey !== $normalizedStage)
                                                    <form action="{{ route('clinicLeadStageUpdate', $lead) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="stage" value="{{ $stageKey }}">
                                                        <button type="submit" class="w-full text-start rounded text-secondary-light hover:bg-neutral-200 text-hover-neutral-900" style="padding: 8px 12px; font-size: 15px;" data-action-menu-close>
                                                            {{ $stageKey === 'booked' ? 'Mark as Booked (Close)' : 'Move to '.$stageOptionLabel }}
                                                        </button>
                                                    </form>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-secondary-light">No leads found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-t border-neutral-200 dark:border-neutral-600">
            {{ $leads->links() }}
        </div>
    </div>

    @foreach ($leads as $lead)
        @php
            $normalizedStage = $normalizeStage((string) $lead->stage);
        @endphp
        <div class="modal fade" id="followUpModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('clinicLeadStageUpdate', $lead) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="stage" value="{{ $normalizedStage }}">
                        <div class="modal-header">
                            <h6 class="modal-title">Add Follow-up</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Due Date & Time</label>
                                <input type="datetime-local" name="follow_up_due_at" class="form-control rounded-lg" required>
                            </div>
                            <div>
                                <label class="form-label">Summary</label>
                                <input type="text" name="follow_up_summary" class="form-control rounded-lg" maxlength="255" placeholder="Follow-up summary" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light px-4 py-2 rounded-lg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Save Follow-up</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editLeadModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form action="{{ route('clinicLeadUpdate', $lead) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h6 class="modal-title">Edit Lead</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" value="{{ $lead->contact?->full_name }}" class="form-control rounded-lg" required>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" value="{{ $lead->contact?->phone }}" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="{{ $lead->contact?->email }}" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Source</label>
                                    <select name="source_platform" class="form-select rounded-lg" required>
                                        @foreach ($sources as $sourceKey => $sourceLabel)
                                            <option value="{{ $sourceKey }}" @selected($lead->source_platform === $sourceKey)>{{ $sourceLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Stage</label>
                                    <select name="stage" class="form-select rounded-lg" required>
                                        @foreach ($stages as $stageKey => $stageLabel)
                                            <option value="{{ $stageKey }}" @selected($normalizeStage((string) $lead->stage) === $stageKey)>{{ $stageLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select rounded-lg" required>
                                        <option value="open" @selected($lead->status === 'open')>Open</option>
                                        <option value="closed" @selected($lead->status === 'closed')>Closed</option>
                                    </select>
                                    <p class="text-xs text-secondary-light mt-1 mb-0">Booked stage will automatically set status to Closed.</p>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Next Follow-up (Optional)</label>
                                    <input type="datetime-local" name="follow_up_due_at" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Follow-up Summary (Optional)</label>
                                    <input type="text" name="follow_up_summary" class="form-control rounded-lg" maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light px-4 py-2 rounded-lg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Update Lead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdowns = document.querySelectorAll('[data-action-dropdown]');

            if (!dropdowns.length) {
                return;
            }

            const closeAll = (except = null) => {
                dropdowns.forEach((dropdown) => {
                    if (except && dropdown === except) {
                        return;
                    }

                    const button = dropdown.querySelector('[data-action-dropdown-button]');
                    const menu = dropdown.querySelector('[data-action-dropdown-menu]');

                    if (menu) {
                        menu.classList.add('hidden');
                    }

                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }

                    dropdown.style.zIndex = '20';
                });
            };

            dropdowns.forEach((dropdown) => {
                const button = dropdown.querySelector('[data-action-dropdown-button]');
                const menu = dropdown.querySelector('[data-action-dropdown-menu]');

                if (!button || !menu) {
                    return;
                }

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const shouldOpen = menu.classList.contains('hidden');
                    closeAll(dropdown);

                    if (shouldOpen) {
                        menu.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        dropdown.style.zIndex = '9999';
                    } else {
                        menu.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                        dropdown.style.zIndex = '20';
                    }
                });

                dropdown.querySelectorAll('[data-action-menu-close]').forEach((menuItem) => {
                    menuItem.addEventListener('click', function () {
                        menu.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                    });
                });
            });

            document.addEventListener('click', function (event) {
                const target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                if (!target.closest('[data-action-dropdown]')) {
                    closeAll();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAll();
                }
            });
        });
    </script>

@endsection
