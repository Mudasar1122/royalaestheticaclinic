@extends('layout.layout')

@php
    $title = 'User Management';
    $subTitle = 'Users List';
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card border-0 followup-grid-card">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex items-center justify-between gap-3">
            <h6 class="mb-0 font-semibold text-lg">Users List</h6>
            <a href="{{ route('addUser') }}" class="btn btn-primary text-sm btn-sm px-3 py-3 rounded-lg flex items-center gap-2">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add New User
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="users-grid-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>S.L</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $userItem)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        @if ($userItem->profile_photo_path)
                                            <img src="{{ asset('storage/'.$userItem->profile_photo_path) }}" alt="User" class="w-10 h-10 rounded-full object-cover shrink-0">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-600/25 text-primary-600 flex items-center justify-center font-semibold shrink-0">
                                                {{ strtoupper(substr($userItem->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <span class="text-base mb-0 font-normal text-secondary-light">{{ $userItem->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $userItem->email }}</td>
                                <td>{{ $userItem->phone ?: '-' }}</td>
                                <td>{{ ucfirst($userItem->role) }}</td>
                                <td class="text-center">
                                    <span class="px-4 py-1.5 rounded font-medium text-sm {{ $userItem->is_active ? 'bg-success-100 dark:bg-success-600/25 text-success-600 dark:text-success-300 border border-success-600' : 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200 border border-neutral-400' }}">
                                        {{ $userItem->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
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
                                            <a
                                                href="{{ route('usersPermissionsEdit', $userItem) }}"
                                                class="followup-action-item"
                                                data-action-menu-close
                                            >
                                                Assign Permission
                                            </a>
                                            <a
                                                href="{{ route('usersEdit', $userItem) }}"
                                                class="followup-action-item"
                                                data-action-menu-close
                                            >
                                                Edit User
                                            </a>
                                            @if (!$userItem->isAdmin())
                                                <form method="POST" action="{{ route('usersImpersonate', $userItem) }}" class="followup-action-form">
                                                    @csrf
                                                    <button type="submit" class="followup-action-item" data-action-menu-close>
                                                        Login As User
                                                    </button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('usersToggleStatus', $userItem) }}" class="followup-action-form">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="is_active" value="{{ $userItem->is_active ? 0 : 1 }}">
                                                <button type="submit" class="followup-action-item" data-action-menu-close>
                                                    {{ $userItem->is_active ? 'Deactivate User' : 'Activate User' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10 text-secondary-light">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <style>
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

        .followup-grid-card .datatable-wrapper .datatable-dropdown {
            white-space: nowrap;
        }

        .followup-grid-card .datatable-wrapper .datatable-selector {
            min-width: 86px;
        }

        @media (min-width: 992px) {
            .followup-grid-card,
            .followup-grid-card .card-body,
            .followup-grid-card .table-responsive,
            .followup-grid-card .datatable-wrapper,
            .followup-grid-card .datatable-wrapper .datatable-container {
                overflow: visible;
            }
        }

        #users-grid-table {
            width: 100%;
        }

        #users-grid-table th,
        #users-grid-table td {
            vertical-align: top;
            white-space: normal !important;
            word-break: break-word;
        }

        #users-grid-table th:nth-child(7),
        #users-grid-table td:nth-child(7) {
            min-width: 128px;
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

        @media (max-width: 991px) {
            .followup-grid-card .table-responsive {
                overflow-x: auto;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('users-grid-table');

            if (table && typeof simpleDatatables !== 'undefined' && typeof simpleDatatables.DataTable !== 'undefined') {
                const usersTable = new simpleDatatables.DataTable('#users-grid-table', {
                    searchable: true,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    columns: [
                        { select: [6], sortable: false, searchable: false },
                    ],
                    labels: {
                        placeholder: 'Search...',
                        perPage: 'Rows per page',
                        noRows: 'No users found.',
                        info: 'Showing {start} to {end} of {rows} entries',
                    },
                });

                if (window.royalUi && typeof window.royalUi.enableDatatableAllOption === 'function') {
                    window.royalUi.enableDatatableAllOption(usersTable);
                }
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
                    closeAll(dropdown);

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
