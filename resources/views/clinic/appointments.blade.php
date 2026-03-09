@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Follow-up Queue';
    $canMarkBooked = auth()->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false;

    $tabs = [
        'today' => [
            'label' => "Today's Follow Up",
            'count' => $todayCount ?? 0,
            'icon' => 'heroicons:calendar-days',
        ],
        'pending' => [
            'label' => 'Pending Follow Up',
            'count' => $pendingCount ?? 0,
            'icon' => 'heroicons:hourglass',
        ],
        'upcoming' => [
            'label' => 'UpComing Follow Up',
            'count' => $upcomingCount ?? 0,
            'icon' => 'heroicons:clock',
        ],
    ];
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-4">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('whatsapp'))
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-4">
            {{ $errors->first('whatsapp') }}
        </div>
    @endif

    @if ($errors->has('follow_up_due_at'))
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-4">
            {{ $errors->first('follow_up_due_at') }}
        </div>
    @endif

    <div class="followup-tab-wrap mb-6">
        @foreach ($tabs as $tabKey => $tabMeta)
            <a
                href="{{ route('clinicAppointments', ['tab' => $tabKey]) }}"
                class="followup-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
            >
                <div class="followup-tab__content">
                    <iconify-icon icon="{{ $tabMeta['icon'] }}" class="followup-tab__icon"></iconify-icon>
                    <span class="followup-tab__label">{{ $tabMeta['label'] }}</span>
                    <span class="followup-tab__badge">{{ number_format((int) $tabMeta['count']) }}</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card border-0 followup-grid-card">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">{{ $tabs[$activeTab]['label'] ?? "Today's Follow Up" }}</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="followup-grid-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone No</th>
                            <th>Procedure of Interest</th>
                            <th>Stage</th>
                            <th>Last Follow-up</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $rawStage = (string) $lead->stage;
                                $normalizedStage = match ($rawStage) {
                                    'initial' => 'new',
                                    'proposal' => 'negotiation',
                                    'confirmed' => 'booked',
                                    default => $rawStage,
                                };
                                $stageLabel = $stages[$normalizedStage] ?? ucfirst(str_replace('_', ' ', (string) $normalizedStage));

                                $procedureKeys = collect(data_get($lead->meta, 'procedures_of_interest', []))
                                    ->map(static fn ($value): string => (string) $value)
                                    ->filter(static fn (string $value): bool => $value !== '')
                                    ->unique()
                                    ->values()
                                    ->all();

                                $procedureLabels = collect($procedureKeys)
                                    ->map(function (string $procedureKey) use ($procedureOptions, $lead): string {
                                        if ($procedureKey === 'other') {
                                            $otherValue = trim((string) data_get($lead->meta, 'procedure_other', ''));

                                            return $otherValue !== '' ? 'Other: '.$otherValue : 'Other';
                                        }

                                        return $procedureOptions[$procedureKey] ?? ucfirst(str_replace('_', ' ', $procedureKey));
                                    })
                                    ->filter(static fn (string $value): bool => trim($value) !== '')
                                    ->values()
                                    ->all();

                                $leadSearchValue = trim((string) ($lead->contact?->phone ?? '')) !== ''
                                    ? (string) $lead->contact?->phone
                                    : (string) ($lead->contact?->full_name ?? '');
                            @endphp
                            <tr>
                                <td>
                                    <a
                                        href="{{ route('clinicLeadFollowUp', $lead) }}"
                                        class="font-medium text-neutral-700 dark:text-neutral-100 hover:text-primary-600"
                                    >
                                        {{ $lead->contact?->full_name ?? 'Unnamed Lead' }}
                                    </a>
                                </td>
                                <td>
                                    {{ $lead->contact?->phone ?? '-' }}
                                </td>
                                <td>
                                    @if (!empty($procedureLabels))
                                        <ul class="procedure-stack">
                                            @foreach ($procedureLabels as $procedureLabel)
                                                <li>{{ $procedureLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-sm text-secondary-light">Not selected</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="followup-stage-pill px-3 py-1 rounded-full text-xs font-semibold bg-primary-100 dark:bg-primary-600/25 text-primary-700 dark:text-primary-300">
                                        {{ $stageLabel }}
                                    </span>
                                </td>
                                <td>
                                    @if ($lead->last_follow_up_at)
                                        {{ \Illuminate\Support\Carbon::parse((string) $lead->last_follow_up_at)->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $showMarkBooked = $canMarkBooked && $normalizedStage !== 'booked';
                                    @endphp
                                    @if ($showMarkBooked)
                                        <div class="followup-action-dropdown" data-action-dropdown>
                                            <button
                                                type="button"
                                                class="followup-action-btn btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                                                data-action-dropdown-button
                                                aria-expanded="false"
                                            >
                                                Action
                                                <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                                            </button>
                                            <div
                                                class="followup-action-menu hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                                                data-action-dropdown-menu
                                            >
                                                <form action="{{ route('clinicLeadStageUpdate', $lead) }}" method="POST" class="followup-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="stage" value="booked">
                                                    <button
                                                        type="submit"
                                                        class="followup-action-item"
                                                        data-action-menu-close
                                                    >
                                                        Mark as Booked
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-secondary-light">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-secondary-light">No follow-ups available in this queue.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .followup-tab-wrap {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
            border: 1px solid #d4d7dd;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .followup-tab {
            border-right: 1px solid #d4d7dd;
            min-height: 66px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            text-decoration: none;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .followup-tab:last-child {
            border-right: 0;
        }

        .followup-tab.active {
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            box-shadow: inset 0 3px 0 rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab__content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 12px;
            text-align: center;
            flex-wrap: wrap;
        }

        .followup-tab__icon {
            font-size: 18px;
            color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab__label {
            font-size: 1rem;
            color: #344054;
            font-weight: 600;
        }

        .followup-tab.active .followup-tab__label {
            color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab__badge {
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 4px 10px;
            line-height: 1;
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab.active .followup-tab__badge {
            background: rgb(var(--ra-primary-rgb, 190 133 0));
            color: #fff;
        }

        .followup-grid-card .table {
            min-width: 100% !important;
            table-layout: auto;
        }

        .followup-grid-card .datatable-wrapper .datatable-top {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0;
        }

        .followup-grid-card .datatable-wrapper .datatable-bottom {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 0;
        }

        .followup-grid-card .datatable-wrapper .datatable-search .datatable-input {
            border: 1px solid #d4d7dd;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .followup-grid-card .datatable-wrapper .datatable-search .datatable-input:focus {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
            box-shadow: none;
        }

        @media (min-width: 992px) {
            .followup-grid-card .table-responsive,
            .followup-grid-card .datatable-wrapper .datatable-container {
                overflow: visible;
            }
        }

        #followup-grid-table {
            width: 100%;
        }

        #followup-grid-table th,
        #followup-grid-table td {
            white-space: nowrap !important;
            word-break: normal !important;
            vertical-align: top;
        }

        #followup-grid-table th:nth-child(1),
        #followup-grid-table td:nth-child(1) {
            width: 16%;
            min-width: 190px;
        }

        #followup-grid-table th:nth-child(2),
        #followup-grid-table td:nth-child(2) {
            width: 14%;
            min-width: 165px;
        }

        #followup-grid-table th:nth-child(3),
        #followup-grid-table td:nth-child(3) {
            width: 26%;
            text-align: left;
            white-space: normal !important;
            min-width: 280px;
        }

        #followup-grid-table th:nth-child(4),
        #followup-grid-table td:nth-child(4) {
            width: 10%;
            min-width: 120px;
        }

        #followup-grid-table th:nth-child(5),
        #followup-grid-table td:nth-child(5) {
            width: 18%;
            min-width: 210px;
        }

        #followup-grid-table th:nth-child(6),
        #followup-grid-table td:nth-child(6) {
            width: 10%;
            min-width: 132px;
        }

        #followup-grid-table td:nth-child(3) {
            min-width: 260px;
        }

        .followup-stage-pill {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .followup-action-dropdown {
            position: relative;
            display: inline-block;
            z-index: 1;
        }

        .followup-action-dropdown.is-open {
            z-index: 2500;
        }

        .followup-action-btn {
            min-width: 96px;
            justify-content: center;
            white-space: nowrap;
        }

        .followup-action-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 2510;
            min-width: 220px;
            width: max-content !important;
            padding: 0.35rem;
            overflow: hidden;
        }

        .followup-action-menu.open-up {
            top: auto;
            bottom: calc(100% + 8px);
        }

        .followup-action-menu > * + * {
            border-top: 1px solid #eef0f3;
        }

        .followup-action-form {
            margin: 0;
        }

        .followup-action-item {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            white-space: nowrap;
            padding: 10px 12px;
            font-size: 14px;
            color: #475467;
            border-radius: 8px;
        }

        .followup-action-item:hover {
            background: #f2f4f7;
            color: #101828;
        }

        .procedure-stack {
            margin: 0;
            padding-left: 0;
            list-style: none;
            display: grid;
            gap: 4px;
        }

        .procedure-stack li {
            font-size: 0.88rem;
            color: #344054;
            line-height: 1.35;
            position: relative;
            padding-left: 12px;
        }

        .procedure-stack li::before {
            content: '\2022';
            position: absolute;
            left: 0;
            color: #475467;
        }

        @media (max-width: 991px) {
            .followup-tab-wrap {
                grid-template-columns: 1fr;
            }

            .followup-tab {
                border-right: 0;
                border-bottom: 1px solid #d4d7dd;
            }

            .followup-tab:last-child {
                border-bottom: 0;
            }

            .followup-grid-card .table-responsive {
                overflow-x: auto;
            }

            .followup-grid-card .table {
                min-width: 960px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('followup-grid-table');

            if (table && typeof simpleDatatables !== 'undefined' && typeof simpleDatatables.DataTable !== 'undefined') {
                new simpleDatatables.DataTable('#followup-grid-table', {
                    searchable: true,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    columns: [
                        { select: [5], sortable: false, searchable: false },
                    ],
                    labels: {
                        placeholder: 'Search...',
                        perPage: 'Rows per page',
                        noRows: 'No follow-ups available in this queue.',
                        info: 'Showing {start} to {end} of {rows} entries',
                    },
                });
            }

            const getDropdowns = function () {
                return Array.from(document.querySelectorAll('[data-action-dropdown]'));
            };

            const closeAll = function (except = null) {
                getDropdowns().forEach((dropdown) => {
                    if (except && dropdown === except) {
                        return;
                    }

                    const menu = dropdown.querySelector('[data-action-dropdown-menu]');
                    const button = dropdown.querySelector('[data-action-dropdown-button]');

                    if (menu) {
                        menu.classList.add('hidden');
                        menu.classList.remove('open-up');
                    }

                    dropdown.classList.remove('is-open');

                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const placeMenu = function (button, menu) {
                menu.classList.remove('open-up');

                const buttonRect = button.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const spaceBelow = window.innerHeight - buttonRect.bottom;
                const spaceAbove = buttonRect.top;
                const neededHeight = menuRect.height + 16;

                if (spaceBelow < neededHeight && spaceAbove > neededHeight) {
                    menu.classList.add('open-up');
                }
            };

            document.addEventListener('click', function (event) {
                const target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                const button = target.closest('[data-action-dropdown-button]');

                if (button) {
                    event.preventDefault();
                    event.stopPropagation();

                    const dropdown = button.closest('[data-action-dropdown]');
                    const menu = dropdown ? dropdown.querySelector('[data-action-dropdown-menu]') : null;

                    if (!dropdown || !menu) {
                        return;
                    }

                    const shouldOpen = menu.classList.contains('hidden');
                    closeAll(dropdown);

                    if (shouldOpen) {
                        dropdown.classList.add('is-open');
                        menu.classList.remove('hidden');
                        placeMenu(button, menu);
                        button.setAttribute('aria-expanded', 'true');
                    } else {
                        menu.classList.add('hidden');
                        menu.classList.remove('open-up');
                        dropdown.classList.remove('is-open');
                        button.setAttribute('aria-expanded', 'false');
                    }

                    return;
                }

                if (target.closest('[data-action-menu-close]')) {
                    closeAll();
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

            window.addEventListener('resize', function () {
                closeAll();
            });

            window.addEventListener('scroll', function () {
                closeAll();
            }, true);
        });
    </script>
@endsection
