@extends('front.layouts.guest')

@section('content')
<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6">

            <div class="text-center mb-4">
                @include('front.partials.auth-brand')
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-4 p-md-5">

                    <h4 class="fw-bold mb-1 text-center">Create an account</h4>
                    <p class="text-muted text-center mb-4 small">Fill in your details to get started</p>

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-3">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li class="small">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label fw-semibold small">Full Name</label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="form-control rounded-3 @error('name') is-invalid @enderror"
                                required
                                autofocus
                                autocomplete="name"
                                placeholder="John Doe"
                            />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Email Address</label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control rounded-3 @error('email') is-invalid @enderror"
                                required
                                autocomplete="username"
                                placeholder="you@example.com"
                            />
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label fw-semibold small">Phone Number</label>
                            <input
                                id="phone"
                                type="tel"
                                name="phone"
                                value="{{ old('phone') }}"
                                class="form-control rounded-3 @error('phone') is-invalid @enderror"
                                required
                                autocomplete="tel"
                                placeholder="+250 7XX XXX XXX"
                            />
                            @error('phone')
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
                                autocomplete="new-password"
                                placeholder="Min. 8 characters"
                            />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold small">Confirm Password</label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                class="form-control rounded-3"
                                required
                                autocomplete="new-password"
                                placeholder="Repeat your password"
                            />
                        </div>

                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="mb-4">
                                <div class="form-check">
                                    <input
                                        class="form-check-input @error('terms') is-invalid @enderror"
                                        type="checkbox"
                                        name="terms"
                                        id="terms"
                                        required
                                    />
                                    <label class="form-check-label small text-muted" for="terms">
                                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-decoration-none text-success">'.__('Terms of Service').'</a>',
                                            'privacy_policy'   => '<a target="_blank" href="'.route('policy.show').'" class="text-decoration-none text-success">'.__('Privacy Policy').'</a>',
                                        ]) !!}
                                    </label>
                                    @error('terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="d-grid">
                            <button type="submit" class="btn rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;">
                                Create Account
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <p class="text-center text-muted small mt-4">
                Already have an account?
                <a href="{{ route('home') }}" class="text-decoration-none fw-semibold text-success">Sign in</a>
            </p>

        </div>
    </div>
</div>
@endsection