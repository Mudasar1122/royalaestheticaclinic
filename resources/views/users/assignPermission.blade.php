@extends('layout.layout')

@php
    $title = 'User Management';
    $subTitle = 'Assign Permission';
    $assignedModules = is_array($editUser->module_access) ? $editUser->module_access : [];
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card border-0 overflow-hidden">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex items-center justify-between gap-3">
            <div>
                <h6 class="mb-1 font-semibold text-lg">Assign Permission</h6>
                <p class="mb-0 text-sm text-secondary-light">{{ $editUser->name }} ({{ ucfirst($editUser->role) }})</p>
            </div>
            <a href="{{ route('usersList') }}" class="btn btn-outline-primary-600 text-sm px-4 py-2 rounded-lg">
                Back to Users
            </a>
        </div>

        <div class="card-body p-6">
            @if ($editUser->isAdmin())
                <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-success-700">
                    Administrator has full access to every module and operation.
                </div>
            @elseif (empty($assignedModules))
                <div class="rounded-lg border border-warning-200 bg-warning-50 px-4 py-3 text-warning-700 mb-4">
                    No module is assigned to this user. Assign modules first from Edit User page.
                </div>
                <a href="{{ route('usersEdit', $editUser) }}" class="btn btn-primary text-sm px-4 py-2 rounded-lg">
                    Edit User Modules
                </a>
            @else
                <form action="{{ route('usersPermissionsUpdate', $editUser) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                        @foreach ($assignedModules as $moduleKey)
                            @continue(!isset($moduleOptions[$moduleKey], $permissionOptions[$moduleKey]))

                            @php
                                $selectedOperations = old('permissions.'.$moduleKey, $selectedPermissions[$moduleKey] ?? []);
                                $selectedOperations = is_array($selectedOperations) ? $selectedOperations : [];
                            @endphp

                            <div class="permission-card border border-neutral-200 dark:border-neutral-600 rounded-xl p-4" data-permission-module>
                                <div class="flex items-center justify-between gap-3 mb-3">
                                    <h6 class="mb-0 text-base font-semibold">{{ $moduleOptions[$moduleKey] }}</h6>
                                    <label class="form-check style-check flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-200">
                                        <input
                                            type="checkbox"
                                            class="form-check-input border border-neutral-300"
                                            data-select-all
                                        >
                                        <span>Select All</span>
                                    </label>
                                </div>

                                @if ($moduleKey === 'lead_management')
                                    <p class="text-xs text-secondary-light mb-3">
                                        Use All Leads or Own Leads Only to control which leads this user can see.
                                    </p>
                                @endif

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach ($permissionOptions[$moduleKey] as $operationKey => $operationLabel)
                                        <label class="form-check style-check flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                name="permissions[{{ $moduleKey }}][]"
                                                value="{{ $operationKey }}"
                                                class="form-check-input border border-neutral-300"
                                                data-operation-checkbox
                                                @checked(in_array($operationKey, $selectedOperations, true))
                                            >
                                            <span class="text-sm text-neutral-700 dark:text-neutral-200">{{ $operationLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('permissions.'.$moduleKey)
                                    <p class="text-danger-600 text-sm mt-3 mb-0">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="flex items-center justify-end gap-3 mt-6">
                        <a href="{{ route('usersList') }}" class="btn btn-cancel px-4 py-2 rounded-lg text-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg text-sm">Save Permissions</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modules = document.querySelectorAll('[data-permission-module]');

            modules.forEach((moduleCard) => {
                const selectAll = moduleCard.querySelector('[data-select-all]');
                const checkboxes = Array.from(moduleCard.querySelectorAll('[data-operation-checkbox]'));

                if (!selectAll || checkboxes.length === 0) {
                    return;
                }

                const refreshSelectAll = function () {
                    const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
                    selectAll.checked = checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                };

                selectAll.addEventListener('change', function () {
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    refreshSelectAll();
                });

                checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', refreshSelectAll);
                });

                refreshSelectAll();
            });
        });
    </script>
@endsection
