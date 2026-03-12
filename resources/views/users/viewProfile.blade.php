@extends('layout.layout')

@php
    $title = 'My Profile';
    $subTitle = 'Account Settings';
@endphp

@section('content')
    @if (session('password_status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('password_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-4">
            <div class="user-grid-card relative border border-neutral-200 dark:border-neutral-600 rounded-2xl overflow-hidden bg-white dark:bg-neutral-700 h-full">
                <img src="{{ asset('assets/images/user-grid/user-grid-bg1.png') }}" alt="" class="w-full object-cover">
                <div class="pb-6 ms-6 mb-6 me-6 -mt-[100px]">
                    <div class="text-center border-b border-neutral-200 dark:border-neutral-600 pb-4">
                        @if ($user->profile_photo_path)
                            <img src="{{ asset('storage/'.$user->profile_photo_path) }}" alt="" class="border border-white border-2 w-[180px] h-[180px] rounded-full object-cover mx-auto">
                        @else
                            <div class="border border-white border-2 w-[180px] h-[180px] rounded-full bg-primary-100 dark:bg-primary-600/25 text-primary-600 text-6xl font-semibold mx-auto flex items-center justify-center">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endif
                        <h6 class="mb-0 mt-4">{{ $user->name }}</h6>
                        <span class="text-secondary-light mb-4">{{ $user->email }}</span>
                    </div>
                    <div class="mt-6">
                        <h6 class="text-xl mb-4">Personal Info</h6>
                        <ul>
                            <li class="flex items-center gap-1 mb-3">
                                <span class="w-[35%] text-base font-semibold text-neutral-600 dark:text-neutral-200">Full Name</span>
                                <span class="w-[65%] text-secondary-light font-medium">: {{ $user->name }}</span>
                            </li>
                            <li class="flex items-center gap-1 mb-3">
                                <span class="w-[35%] text-base font-semibold text-neutral-600 dark:text-neutral-200">Email</span>
                                <span class="w-[65%] text-secondary-light font-medium">: {{ $user->email }}</span>
                            </li>
                            <li class="flex items-center gap-1 mb-3">
                                <span class="w-[35%] text-base font-semibold text-neutral-600 dark:text-neutral-200">Phone</span>
                                <span class="w-[65%] text-secondary-light font-medium">: {{ $user->phone ?: '-' }}</span>
                            </li>
                            <li class="flex items-center gap-1">
                                <span class="w-[35%] text-base font-semibold text-neutral-600 dark:text-neutral-200">Role</span>
                                <span class="w-[65%] text-secondary-light font-medium">: {{ ucfirst($user->role) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-12 lg:col-span-8">
            <div class="card h-full border-0">
                <div class="card-body p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none h-full">
                            <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
                                <h6 class="mb-0 font-semibold">Update Profile</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('updateProfile') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" name="name" class="form-control rounded-lg" value="{{ old('name', $user->name) }}" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control rounded-lg bg-neutral-100 dark:bg-neutral-600" value="{{ $user->email }}" readonly>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control rounded-lg bg-neutral-100 dark:bg-neutral-600" value="{{ ucfirst($user->role) }}" readonly>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="phone" class="form-control rounded-lg" value="{{ old('phone', $user->phone) }}">
                                    </div>
                                    <div class="mb-5">
                                        <label class="form-label">Profile Photo</label>
                                        <input type="file" name="profile_photo" class="form-control rounded-lg" accept=".png,.jpg,.jpeg">
                                    </div>
                                    <p class="text-xs text-secondary-light mb-5">Email and role are controlled by admin and cannot be changed here.</p>
                                    <button type="submit" class="btn btn-primary px-6 py-3 rounded-lg">Save Profile</button>
                                </form>
                            </div>
                        </div>

                        <div class="card border border-neutral-200 dark:border-neutral-600 rounded-lg shadow-none h-full">
                            <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
                                <h6 class="mb-0 font-semibold">Change Password</h6>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('updateProfilePassword') }}" method="POST">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label">Current Password</label>
                                        <input type="password" name="current_password" class="form-control rounded-lg" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">New Password</label>
                                        <input type="password" name="password" class="form-control rounded-lg" required>
                                    </div>
                                    <div class="mb-5">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" name="password_confirmation" class="form-control rounded-lg" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-6 py-3 rounded-lg">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
