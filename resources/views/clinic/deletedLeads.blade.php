@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Deleted Leads';

    $readableSource = static function (string $source) use ($sources): string {
        return $sources[$source] ?? ucfirst(str_replace('_', ' ', $source));
    };

    $formatPhoneOnly = static function ($contact): string {
        $phone = trim((string) ($contact?->phone ?? ''));

        return $phone !== '' ? $phone : '-';
    };
@endphp

@section('content')
    <div class="card border-0 deleted-leads-card">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Deleted Leads</h6>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="deleted-leads-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone No</th>
                            <th>Source</th>
                            <th>Deleted At</th>
                            <th>Last Follow-up</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>
                                    <span class="font-medium text-neutral-700 dark:text-neutral-100">
                                        {{ $lead->contact?->full_name ?? 'Unnamed Lead' }}
                                    </span>
                                </td>
                                <td>{{ $formatPhoneOnly($lead->contact) }}</td>
                                <td>{{ $readableSource((string) $lead->source_platform) }}</td>
                                <td>
                                    @if ($lead->deleted_at)
                                        {{ $lead->deleted_at->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($lead->last_follow_up_at)
                                        {{ \Illuminate\Support\Carbon::parse((string) $lead->last_follow_up_at)->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="deleted-leads-action-dropdown" data-action-dropdown>
                                        <button
                                            type="button"
                                            class="deleted-leads-action-btn btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                                            data-action-dropdown-button
                                            aria-expanded="false"
                                            aria-controls="deleted-lead-action-menu-{{ $lead->id }}"
                                        >
                                            Action
                                            <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                                        </button>
                                        <div
                                            id="deleted-lead-action-menu-{{ $lead->id }}"
                                            class="deleted-leads-action-menu hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                                            data-action-dropdown-menu
                                        >
                                            <form action="{{ route('clinicLeadRestore', $lead->id) }}" method="POST" class="deleted-leads-action-form" data-restore-lead-form>
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="deleted-leads-action-item" data-action-menu-close>
                                                    Restore
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-secondary-light">No deleted leads found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
        .deleted-leads-card .datatable-wrapper .datatable-top {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0;
        }

        .deleted-leads-card .datatable-wrapper .datatable-bottom {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 0;
        }

        .deleted-leads-card .datatable-wrapper .datatable-search .datatable-input {
            border: 1px solid #d4d7dd;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .deleted-leads-card .datatable-wrapper .datatable-search .datatable-input:focus {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
            box-shadow: none;
        }

        #deleted-leads-table {
            width: 100%;
        }

        .deleted-leads-card .table {
            min-width: 100% !important;
            table-layout: fixed;
        }

        #deleted-leads-table th,
        #deleted-leads-table td {
            white-space: normal !important;
            word-break: break-word !important;
            vertical-align: top;
        }

        #deleted-leads-table th:nth-child(1),
        #deleted-leads-table td:nth-child(1) {
            width: 20%;
        }

        #deleted-leads-table th:nth-child(2),
        #deleted-leads-table td:nth-child(2) {
            width: 14%;
            white-space: nowrap !important;
        }

        #deleted-leads-table th:nth-child(3),
        #deleted-leads-table td:nth-child(3) {
            width: 14%;
        }

        #deleted-leads-table th:nth-child(4),
        #deleted-leads-table td:nth-child(4) {
            width: 18%;
        }

        #deleted-leads-table th:nth-child(5),
        #deleted-leads-table td:nth-child(5) {
            width: 20%;
        }

        #deleted-leads-table th:nth-child(6),
        #deleted-leads-table td:nth-child(6) {
            width: 14%;
            white-space: nowrap !important;
        }

        .deleted-leads-action-dropdown {
            position: relative;
            display: inline-block;
            z-index: 1;
        }

        .deleted-leads-action-dropdown.is-open {
            z-index: 2500;
        }

        .deleted-leads-action-btn {
            min-width: 96px;
            justify-content: center;
            white-space: nowrap;
        }

        .deleted-leads-action-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 2510;
            min-width: 220px;
            width: max-content !important;
            padding: 0.35rem;
            overflow: hidden;
        }

        .deleted-leads-action-menu.open-up {
            top: auto;
            bottom: calc(100% + 8px);
        }

        .deleted-leads-action-form {
            margin: 0;
        }

        .deleted-leads-action-item {
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

        .deleted-leads-action-item:hover {
            background: #f2f4f7;
            color: #101828;
        }

    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const actionDropdowns = Array.from(document.querySelectorAll('[data-action-dropdown]'));

            const closeAllDropdowns = function (except = null) {
                actionDropdowns.forEach((dropdown) => {
                    if (except && dropdown === except) {
                        return;
                    }

                    const menu = dropdown.querySelector('[data-action-dropdown-menu]');
                    const button = dropdown.querySelector('[data-action-dropdown-button]');

                    if (menu) {
                        menu.classList.add('hidden');
                        if (window.royalUi && typeof window.royalUi.resetActionDropdown === 'function') {
                            window.royalUi.resetActionDropdown(menu);
                        } else {
                            menu.classList.remove('open-up');
                        }
                    }

                    dropdown.classList.remove('is-open');

                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const placeMenu = function (button, menu) {
                if (window.royalUi && typeof window.royalUi.placeActionDropdown === 'function') {
                    window.royalUi.placeActionDropdown(button, menu);
                    return;
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
                    closeAllDropdowns(dropdown);

                    if (shouldOpen) {
                        dropdown.classList.add('is-open');
                        menu.classList.remove('hidden');
                        placeMenu(button, menu);
                        button.setAttribute('aria-expanded', 'true');
                    } else {
                        menu.classList.add('hidden');
                        if (window.royalUi && typeof window.royalUi.resetActionDropdown === 'function') {
                            window.royalUi.resetActionDropdown(menu);
                        } else {
                            menu.classList.remove('open-up');
                        }
                        dropdown.classList.remove('is-open');
                        button.setAttribute('aria-expanded', 'false');
                    }

                    return;
                }

                if (target.closest('[data-action-menu-close]')) {
                    closeAllDropdowns();
                    return;
                }

                if (!target.closest('[data-action-dropdown]')) {
                    closeAllDropdowns();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAllDropdowns();
                }
            });

            window.addEventListener('resize', function () {
                closeAllDropdowns();
            });

            window.addEventListener('scroll', function () {
                closeAllDropdowns();
            }, true);

            document.querySelectorAll('[data-restore-lead-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm('Restore this lead?')) {
                        event.preventDefault();
                    }
                });
            });

            if (document.getElementById('deleted-leads-table') && typeof simpleDatatables !== 'undefined' && typeof simpleDatatables.DataTable !== 'undefined') {
                new simpleDatatables.DataTable('#deleted-leads-table', {
                    searchable: true,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    labels: {
                        placeholder: 'Search...',
                        perPage: 'Rows per page',
                        noRows: 'No deleted leads found.',
                        info: 'Showing {start} to {end} of {rows} entries',
                    },
                });
            }
        });
    </script>
@endsection
