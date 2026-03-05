<!DOCTYPE html>
<html lang="en">

<x-head />

<body class="dark:bg-neutral-800 bg-neutral-100 dark:text-white">
    <section class="min-h-[100vh] bg-neutral-100 dark:bg-dark-2 flex flex-col auth-split">
        <div class="lg:w-1/2 auth-left px-6 sm:px-10 lg:px-14 py-12 flex flex-col justify-center">
            <h1 class="mt-1 text-4xl sm:text-4xl font-semibold leading-tight text-neutral-900 dark:text-white">
                Reset Password
            </h1>
            <p class="mt-1 text-base text-neutral-600 dark:text-neutral-300">
                Set your new account password to continue.
            </p>
        </div>

        <div class="lg:w-1/2 auth-right px-6 sm:px-10 lg:px-14 py-12 flex items-center justify-center">
            <div class="auth-card relative w-full lg:max-w-[464px] bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-8 sm:p-10 shadow-sm">
                <a href="{{ route('signin') }}" class="mb-0 inline-block max-w-[290px]">
                    <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Royal Aesthetica">
                </a>

                <h2 class="mt-2 text-3xl font-semibold text-neutral-900 dark:text-white">Create New Password</h2>
                <p class="mt-2 text-neutral-600 dark:text-neutral-300">
                    Choose a strong password with at least 8 characters.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger px-4 py-3 rounded-lg mt-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form class="auth-form mt-5" method="POST" action="{{ route('resetPasswordPost') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <label class="text-sm text-neutral-700 dark:text-neutral-300">Email Address</label>
                    <div class="icon-field mt-2 mb-5 relative">
                        <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl" placeholder="Email" required>
                    </div>

                    <label class="text-sm text-neutral-700 dark:text-neutral-300">New Password</label>
                    <div class="relative mt-2 mb-5">
                        <div class="icon-field">
                            <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span>
                            <input type="password" name="password" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl" id="new-password" placeholder="New password" required>
                        </div>
                        <span class="toggle-password ri-eye-line cursor-pointer absolute end-0 top-1/2 -translate-y-1/2 me-4 text-secondary-light" data-toggle="#new-password"></span>
                    </div>

                    <label class="text-sm text-neutral-700 dark:text-neutral-300">Confirm Password</label>
                    <div class="relative mt-2 mb-6">
                        <div class="icon-field">
                            <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span>
                            <input type="password" name="password_confirmation" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl" id="confirm-password" placeholder="Confirm password" required>
                        </div>
                        <span class="toggle-password ri-eye-line cursor-pointer absolute end-0 top-1/2 -translate-y-1/2 me-4 text-secondary-light" data-toggle="#confirm-password"></span>
                    </div>

                    <button type="submit" class="btn btn-primary justify-center text-sm btn-sm px-3 py-4 w-full rounded-xl mt-2">
                        Reset Password
                    </button>
                </form>
            </div>
        </div>
    </section>

    @php
        $script = '<script>
                        function initializePasswordToggle(toggleSelector) {
                            $(toggleSelector).on("click", function() {
                                $(this).toggleClass("ri-eye-off-line");
                                var input = $($(this).attr("data-toggle"));
                                if (input.attr("type") === "password") {
                                    input.attr("type", "text");
                                } else {
                                    input.attr("type", "password");
                                }
                            });
                        }
                        initializePasswordToggle(".toggle-password");
            </script>';
    @endphp

    <x-script />
</body>

</html>
