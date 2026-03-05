<!DOCTYPE html>
<html lang="en">

<x-head />

<body class="dark:bg-neutral-800 bg-neutral-100 dark:text-white">
    <section class="min-h-[100vh] bg-neutral-100 dark:bg-dark-2 flex flex-col auth-split">
        <div class="lg:w-1/2 auth-left px-6 sm:px-10 lg:px-14 py-12 flex flex-col justify-center">
            <h1 class="mt-1 text-4xl sm:text-4xl font-semibold leading-tight text-neutral-900 dark:text-white">
                Reset Your Password with Confidence
            </h1>
            <p class="mt-1 text-base text-neutral-600 dark:text-neutral-300">
                Enter your registered email to receive a secure reset link and continue your Royal Aesthetica journey.
            </p>
        </div>

        <div class="lg:w-1/2 auth-right px-6 sm:px-10 lg:px-14 py-12 flex items-center justify-center">
            <div class="auth-card relative w-full lg:max-w-[464px] bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-2xl p-8 sm:p-10 shadow-sm">
                <a href="{{ route('signin') }}" class="mb-0 inline-block max-w-[290px]">
                    <img src="{{ asset('assets/images/logo-light.svg') }}" alt="Royal Aesthetica">
                </a>

                <h2 class="mt-2 text-3xl font-semibold text-neutral-900 dark:text-white">Forgot Password</h2>
                <p class="mt-2 text-neutral-600 dark:text-neutral-300">
                    We will send your reset link from info@royalaestheticaclinic.com.
                </p>

                @if (session('status'))
                    <div class="alert alert-success px-4 py-3 rounded-lg mt-4">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger px-4 py-3 rounded-lg mt-4">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form class="auth-form mt-5" method="POST" action="{{ route('forgotPasswordPost') }}">
                    @csrf
                    <label class="text-sm text-neutral-700 dark:text-neutral-300">Email Address</label>
                    <div class="icon-field mt-2 mb-5 relative">
                        <span class="absolute start-4 top-1/2 -translate-y-1/2 pointer-events-none flex text-xl">
                            <iconify-icon icon="mage:email"></iconify-icon>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control h-[56px] ps-11 border-neutral-300 bg-neutral-50 dark:bg-dark-2 rounded-xl" placeholder="Email" required>
                    </div>

                    <button type="submit" class="btn btn-primary justify-center text-sm btn-sm px-3 py-4 w-full rounded-xl mt-4">
                        Send Reset Link
                    </button>

                    <div class="flex justify-end gap-2 mt-4">
                        <a href="{{ route('signin') }}" class="text-primary-600 font-medium hover:underline">Back to Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <x-script />
</body>

</html>
