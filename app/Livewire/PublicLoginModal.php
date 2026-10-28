<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PublicLoginModal extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        if (Auth::guard('customers')->check()) {
            $this->redirectRoute('account.orders');

            return;
        }

        $this->email = Str::lower($this->email);

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $key = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->password = '';

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        if (! Auth::guard('customers')->attempt([
            'email' => Str::lower($this->email),
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->password = '';

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        session()->regenerate();
        $this->password = '';
        $this->redirectRoute('account.orders');
    }

    public function render()
    {
        return view('livewire.public-login-modal');
    }
}
