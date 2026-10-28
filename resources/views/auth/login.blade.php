@extends('front.layouts.auth')

@section('content')
<div class="auth-page-panel">

            <div class="text-center mb-4">
                @include('front.partials.auth-brand')
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">

                    <h4 class="fw-bold mb-1 text-center">Welcome back</h4>
                    <p class="text-muted text-center mb-4 small">Sign in to your account</p>

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li class="small">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @session('status')
                        <div class="alert alert-success rounded-3 small">
                            {{ $value }}
                        </div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control rounded-3 @error('email') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="you@example.com"
                            />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">Password</label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="form-control rounded-3 @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
                                <label class="form-check-label small text-muted" for="remember_me">
                                    Remember me
                                </label>
                            </div>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="small text-decoration-none text-success">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;">
                                Sign In
                            </button>
                        </div>
                    </form>

                </div>
            </div>

</div>
@endsection