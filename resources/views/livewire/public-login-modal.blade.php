<div class="public-login" x-data="{ open: false, showPassword: false }">
    <style>
        [x-cloak] { display: none !important; }
        .public-login-backdrop {
            align-items: center;
            background: rgba(15, 23, 42, .56);
            display: flex;
            inset: 0;
            justify-content: center;
            padding: 16px;
            position: fixed;
            z-index: 1100;
        }
        .public-login-dialog {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, .2);
            max-height: calc(100vh - 32px);
            max-width: 440px;
            overflow-y: auto;
            padding: 28px;
            position: relative;
            width: 100%;
        }
        .public-login-dialog .form-control:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 .2rem rgba(22, 163, 74, .15);
        }
        .public-login-primary {
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
        }
        .public-login-primary:hover,
        .public-login-primary:focus-visible {
            background: #15803d;
            border-color: #15803d;
            color: #fff;
        }
        .public-login-trigger {
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
            white-space: nowrap;
        }
        .public-login-trigger:hover,
        .public-login-trigger:focus-visible {
            background: #15803d;
            border-color: #15803d;
            color: #fff;
        }
        @media (max-width: 575.98px) {
            .public-login-dialog { padding: 22px 18px; }
        }
    </style>

    <button
        type="button"
        class="btn btn-sm public-login-trigger"
        aria-haspopup="dialog"
        aria-controls="public-login-dialog"
        @click="open = true; $nextTick(() => $refs.email.focus())"
    >
        <i class="bi bi-person me-sm-1" aria-hidden="true"></i>
        <span>Sign In</span>
    </button>

    @teleport('body')
    <div
        class="public-login-backdrop"
        x-cloak
        x-show="open"
        x-transition.opacity
        @click.self="open = false"
        @keydown.escape.window="open = false"
        :aria-hidden="!open"
    >
        <section
            id="public-login-dialog"
            class="public-login-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="public-login-title"
            x-trap.inert.noscroll="open"
            x-on:close.stop="open = false"
        >
            <button
                type="button"
                class="btn-close position-absolute top-0 end-0 m-3"
                aria-label="Close sign in"
                @click="open = false"
            ></button>

            <div class="text-center mb-4">
                <h2 id="public-login-title" class="h4 fw-bold mb-1">Welcome back</h2>
                <p class="text-muted small mb-0">Sign in to your Tresca Hub account</p>
            </div>

            <form wire:submit="login" novalidate>
                <div class="mb-3">
                    <label for="public-login-email" class="form-label fw-semibold small">Email address</label>
                    <input
                        id="public-login-email"
                        type="email"
                        class="form-control rounded-3 @error('email') is-invalid @enderror"
                        wire:model="email"
                        autocomplete="username"
                        inputmode="email"
                        required
                        x-ref="email"
                        @error('email') aria-invalid="true" aria-describedby="public-login-email-error" @enderror
                    >
                    @error('email')
                        <div id="public-login-email-error" class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="public-login-password" class="form-label fw-semibold small">Password</label>
                    <div class="input-group">
                        <input
                            id="public-login-password"
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control rounded-start-3 @error('password') is-invalid @enderror"
                            wire:model="password"
                            autocomplete="current-password"
                            required
                            @error('password') aria-invalid="true" aria-describedby="public-login-password-error" @enderror
                        >
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            :aria-pressed="showPassword"
                            @click="showPassword = !showPassword"
                        >
                            <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" aria-hidden="true"></i>
                        </button>
                        @error('password')
                            <div id="public-login-password-error" class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input id="public-login-remember" type="checkbox" class="form-check-input" wire:model="remember">
                        <label for="public-login-remember" class="form-check-label small text-muted">Remember me</label>
                    </div>
                    <a href="{{ route('customer.password.request') }}" class="small text-decoration-none">Forgot password?</a>
                </div>

                <button
                    type="submit"
                    class="btn public-login-primary rounded-3 py-2 fw-semibold w-100"
                    wire:loading.attr="disabled"
                    wire:target="login"
                >
                    <span wire:loading.remove wire:target="login">Sign In</span>
                    <span wire:loading wire:target="login">
                        <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                        Signing in...
                    </span>
                </button>
            </form>

            <p class="text-center text-muted small mt-4 mb-0">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-decoration-none fw-semibold">Create an account</a>
            </p>
        </section>
    </div>
    @endteleport
</div>
