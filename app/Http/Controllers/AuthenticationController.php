<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthenticationController extends Controller
{
    public function forgotPassword()
    {
        if (Auth::check()) {
            return redirect()->route('index');
        }

        return view('authentication.forgotPassword');
    }

    public function signin()
    {
        if (Auth::check()) {
            return redirect()->route('index');
        }

        return view('authentication.signin');
    }

    public function signinPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['password' => 'Invalid username or password.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        if (!Auth::user()?->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['password' => 'Your account is inactive. Please contact admin.'])
                ->withInput($request->only('email'));
        }

        return redirect()->intended(route('index'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin');
    }

    public function forgotPasswordPost(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => __($status)])
            ->withInput();
    }

    public function resetPassword(Request $request, string $token)
    {
        if (Auth::check()) {
            return redirect()->route('index');
        }

        return view('authentication.resetPassword', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPasswordPost(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $validated,
            function ($user) use ($validated): void {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('signin')->with('status', __($status));
        }

        return back()
            ->withErrors(['email' => [__($status)]])
            ->withInput($request->except('password', 'password_confirmation'));
    }

    public function signup()
    {
        return view('authentication.signup');
    }
}
