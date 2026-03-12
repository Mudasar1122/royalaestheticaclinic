@extends('layout.layout')

@php
    $title = 'User Management';
    $subTitle = 'Add User';
@endphp

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card h-full p-0 rounded-xl border-0 overflow-hidden">
        <div class="card-body p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 justify-center">
                <div class="col-span-12 lg:col-span-10 xl:col-span-8 2xl:col-span-6 2xl:col-start-4">
                    <div class="card border border-neutral-200 dark:border-neutral-600">
                        <div class="card-body">
                            <h6 class="text-base text-neutral-600 dark:text-neutral-200 mb-4">Profile Image</h6>

                            <div class="mb-6 mt-4">
                                <div class="avatar-upload">
                                    <div class="avatar-edit absolute bottom-0 end-0 me-6 mt-4 z-[1] cursor-pointer">
                                        <input type="file" id="imageUpload" name="profile_photo" accept=".png, .jpg, .jpeg" hidden form="create-user-form">
                                        <label for="imageUpload" class="w-8 h-8 flex justify-center items-center bg-primary-50 dark:bg-primary-600/25 text-primary-600 dark:text-primary-400 border border-primary-600 hover:bg-primary-100 text-lg rounded-full">
                                            <iconify-icon icon="solar:camera-outline" class="icon"></iconify-icon>
                                        </label>
                                    </div>
                                    <div class="avatar-preview">
                                        <div id="imagePreview"></div>
                                    </div>
                                </div>
                            </div>

                            <form id="create-user-form" action="{{ route('storeUser') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-5">
                                    <label for="name" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Full Name <span class="text-danger-600">*</span></label>
                                    <input type="text" class="form-control rounded-lg" id="name" name="name" value="{{ old('name') }}" placeholder="Enter full name" required>
                                </div>
                                <div class="mb-5">
                                    <label for="email" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Email <span class="text-danger-600">*</span></label>
                                    <input type="email" class="form-control rounded-lg" id="email" name="email" value="{{ old('email') }}" placeholder="Enter email address" required>
                                </div>
                                <div class="mb-5">
                                    <label for="phone" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Phone</label>
                                    <input type="text" class="form-control rounded-lg" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter phone number">
                                </div>
                                <div class="mb-5">
                                    <label for="role" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Role <span class="text-danger-600">*</span></label>
                                    <select class="form-control rounded-lg form-select" id="role" name="role" required>
                                        <option value="admin" @selected(old('role') === 'admin')>Administrator</option>
                                        <option value="manager" @selected(old('role') === 'manager')>Manager</option>
                                        <option value="staff" @selected(old('role', 'staff') === 'staff')>Staff</option>
                                        <option value="viewer" @selected(old('role') === 'viewer')>Viewer</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                                    <div>
                                        <label for="password" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Password <span class="text-danger-600">*</span></label>
                                        <input type="password" class="form-control rounded-lg" id="password" name="password" placeholder="Minimum 8 characters" required>
                                    </div>
                                    <div>
                                        <label for="password_confirmation" class="inline-block font-semibold text-neutral-600 dark:text-neutral-200 text-sm mb-2">Confirm Password <span class="text-danger-600">*</span></label>
                                        <input type="password" class="form-control rounded-lg" id="password_confirmation" name="password_confirmation" placeholder="Re-enter password" required>
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="form-check style-check flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" class="form-check-input border border-neutral-300" @checked(old('is_active', '1') === '1')>
                                        <span class="text-sm text-neutral-700 dark:text-neutral-200">Active account</span>
                                    </label>
                                </div>
                                <div class="flex items-center justify-center gap-3">
                                    <a href="{{ route('usersList') }}" class="btn btn-cancel text-base px-14 py-[11px] rounded-lg">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary border border-primary-600 text-base px-14 py-3 rounded-lg">
                                        Save User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const imageInput = document.getElementById('imageUpload');
            const imagePreview = document.getElementById('imagePreview');

            if (!imageInput || !imagePreview) {
                return;
            }

            imageInput.addEventListener('change', function () {
                const file = imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;

                if (!file) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    const result = event.target && typeof event.target.result === 'string'
                        ? event.target.result
                        : '';

                    if (result !== '') {
                        imagePreview.style.backgroundImage = "url('" + result + "')";
                    }
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endsection
