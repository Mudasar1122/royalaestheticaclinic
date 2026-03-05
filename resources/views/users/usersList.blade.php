@extends('layout.layout')

@php
    $title = 'User Management';
    $subTitle = 'Users List';
@endphp

@section('content')
    @if (session('status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card h-full p-0 rounded-xl border-0 overflow-hidden">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 py-4 px-6 flex items-center flex-wrap gap-3 justify-between">
            <form method="GET" action="{{ route('usersList') }}" class="flex items-center flex-wrap gap-3">
                <input type="text" class="form-control rounded-lg max-w-[220px]" name="search" value="{{ $filters['search'] }}" placeholder="Search user">
                <select class="form-select rounded-lg max-w-[160px]" name="role">
                    <option value="">All Roles</option>
                    <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
                    <option value="manager" @selected($filters['role'] === 'manager')>Manager</option>
                    <option value="staff" @selected($filters['role'] === 'staff')>Staff</option>
                    <option value="viewer" @selected($filters['role'] === 'viewer')>Viewer</option>
                </select>
                <select class="form-select rounded-lg max-w-[160px]" name="status">
                    <option value="">All Status</option>
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary text-sm btn-sm px-3 py-3 rounded-lg">Apply</button>
            </form>

            <a href="{{ route('addUser') }}" class="btn btn-primary text-sm btn-sm px-3 py-3 rounded-lg flex items-center gap-2">
                <iconify-icon icon="ic:baseline-plus" class="icon text-xl line-height-1"></iconify-icon>
                Add New User
            </a>
        </div>
        <div class="card-body p-6">
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
                    <thead>
                        <tr>
                            <th>S.L</th>
                            <th>Join Date</th>
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
                                <td>{{ $loop->iteration + (($users->currentPage() - 1) * $users->perPage()) }}</td>
                                <td>{{ $userItem->created_at?->format('d M Y') }}</td>
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
                                    <div class="flex justify-center gap-2">
                                        <form method="POST" action="{{ route('usersToggleStatus', $userItem) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $userItem->is_active ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm {{ $userItem->is_active ? 'btn-danger' : 'btn-success' }} px-3 py-2 rounded-lg">
                                                {{ $userItem->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-10 text-secondary-light">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer border-t border-neutral-200 dark:border-neutral-600">
            {{ $users->links() }}
        </div>
    </div>
@endsection
