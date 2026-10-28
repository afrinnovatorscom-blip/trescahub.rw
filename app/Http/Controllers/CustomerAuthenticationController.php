<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerAuthenticationController extends Controller
{
    public function register(): View|RedirectResponse
    {
        if (Auth::guard('customers')->check()) {
            return redirect()->route('account.orders');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Auth::guard('customers')->check()) {
            return redirect()->route('account.orders');
        }

        $request->merge(['email' => Str::lower($request->string('email')->toString())]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:customers,email', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);

        $customer = Customer::query()->create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        Auth::guard('customers')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('account.orders');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('customers')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function forgotPassword(): View
    {
        return view('auth.customer-forgot-password');
    }

    public function emailResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $request->merge(['email' => Str::lower($request->string('email')->toString())]);
        Password::broker('customers')->sendResetLink($request->only('email'));

        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function resetPasswordForm(Request $request, string $token): View
    {
        return view('auth.customer-reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', PasswordRule::default(), 'confirmed'],
        ]);
        $data['email'] = Str::lower($data['email']);

        $status = Password::broker('customers')->reset(
            $data,
            function (Customer $customer, string $password): void {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        }

        return redirect()->route('home')->with('status', 'Your password has been reset. You can now sign in.');
    }
}
