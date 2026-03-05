<!DOCTYPE html>
<html lang="en">

<x-head />

<body class="dark:bg-neutral-800 bg-neutral-100 dark:text-white">
    <section class="min-h-[100vh] bg-neutral-100 dark:bg-dark-2 flex flex-col auth-split">
        <div class="lg:w-1/2 auth-left px-6 sm:px-10 lg:px-14 py-12 flex flex-col justify-center">
            <h1 class="mt-1 text-4xl sm:text-4xl font-semibold leading-tight text-neutral-900 dark:text-white">
                Achieve Flawless Skin with Leading Skin and Laser Experts
            </h1>
            <p class="mt-1 text-base text-neutral-600 dark:text-neutral-300">
                Welcome to Royal Aesthetica, your premier destination for aesthetic enhancement. Sign in to continue your journey to radiance and self-confidence.
            </p>

            <div class="mt-10 flex items-center gap-8 flex-wrap">
                <img src="{{ asset('assets/images/sponsors/skingen.png') }}" alt="SkinGen" class="h-8 sm:h-10 object-contain">
                <img src="{{ asset('assets/images/sponsors/sthetica.png') }}" alt="Sthetica" class="h-8 sm:h-10 object-contain">
            </div>
        </div>

        <div class="lg:w-1/2 auth-right px-6 sm:px-10 lg:px-14 py-12 flex items-center justify-center">
            <div class="auth-card relative w-full lg:max-w-[464px] bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-8 sm:p-10 shadow-sm">
                <a href="{{ route('signin') }}" class="mb-0 inline-block max-w-[290px]">
                    <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Royal Aesthetica">
                </a>
                <h2 class="mt-2 text-3xl font-semibold text-neutral-900 dark:text-white">Sign In</h2>
                <p class="mt-2 text-neutral-600 dark:text-neutral-300">Please enter your credentials to access your Royal Aesthetica account.</p>

                @if (session('status'))
                    <div class="alert alert-success px-4 py-3 rounded-lg mt-4">
                        {{ session('status') }}
                    </div>
                @endif

                <form class="auth-form mt-5" method="POST" action="{{ route('signinPost') }}">
                    @csrf
                    <label class="text-sm text-neutral-700 dark:text-neutral-300">Email Address</label>
                    <div class="icon-field mt-2 mb-5 relative">
                        <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl @error('email') border-danger-600 @enderror" placeholder="Email" required>
                    </div>
                    @error('email')
                        <p class="text-danger-600 text-sm">{{ $message }}</p>
                    @enderror

                    <label class="text-sm text-neutral-700 dark:text-neutral-300">Password</label>
                    <div class="relative mt-2 mb-6">
                        <div class="icon-field">
                            <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
                            </span>
                            <input type="password" name="password" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl @error('password') border-danger-600 @enderror" id="your-password" placeholder="Password" required>
                        </div>
                        <button
                            type="button"
                            class="password-toggle absolute end-0 top-1/2 -translate-y-1/2 me-4 text-secondary-light flex items-center"
                            data-target="your-password"
                            aria-label="Show password"
                        >
                            <iconify-icon icon="mdi:eye-outline" class="text-xl"></iconify-icon>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-danger-600 text-sm">{{ $message }}</p>
                    @enderror

                    <div class="flex justify-between gap-2">
                        <div class="flex items-center">
                            <input class="form-check-input border border-neutral-300" type="checkbox" value="1" id="remember" name="remember">
                            <label class="ps-2" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('forgotPassword') }}" class="text-primary-600 font-medium hover:underline">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary justify-center text-sm btn-sm px-3 py-4 w-full rounded-xl mt-8">Sign In</button>
                </form>
            </div>
        </div>
    </section>

    <x-script />
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll(".password-toggle").forEach(function (toggleButton) {
                toggleButton.addEventListener("click", function () {
                    var targetId = toggleButton.getAttribute("data-target");
                    var input = targetId ? document.getElementById(targetId) : null;

                    if (!input) {
                        return;
                    }

                    var isHidden = input.type === "password";
                    input.type = isHidden ? "text" : "password";
                    toggleButton.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");

                    var icon = toggleButton.querySelector("iconify-icon");
                    if (icon) {
                        icon.setAttribute("icon", isHidden ? "mdi:eye-off-outline" : "mdi:eye-outline");
                    }
                });
            });
        });
    </script>
</body>

</html>
