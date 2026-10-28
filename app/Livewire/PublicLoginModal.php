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

    public bool $openOnLoad = false;

    public string $notice = '';

    public function mount(bool $openOnLoad = false, string $notice = ''): void
    {
        $this->openOnLoad = $openOnLoad;
        $this->notice = $notice;
    }

    public function login(): void
    {
        if (Auth::guard('customers')->check()) {
            $this->redirectAfterLogin();

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
        $this->redirectAfterLogin();
    }

    private function redirectAfterLogin(): void
    {
        $intended = session()->pull('customer.auth_redirect', session()->pull('url.intended'));

        if (is_string($intended)) {
            $parts = parse_url($intended);

            if (
                is_array($parts)
                && ($parts['host'] ?? null) === request()->getHost()
                && (int) ($parts['port'] ?? request()->getPort()) === (int) request()->getPort()
            ) {
                $this->redirect($intended);

                return;
            }
        }

        $this->redirectRoute('account.orders');
    }

    public function render()
    {
        return view('livewire.public-login-modal');
    }
}
