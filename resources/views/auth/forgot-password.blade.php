@extends('front.layouts.guest')

@section('title', 'Forgot Password')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 520px;">
        <div class="text-center mb-4">@include('front.partials.auth-brand')</div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-2">Reset your password</h1>
                <p class="text-muted mb-4">Enter your email address and we will send you a link to choose a new password.</p>

                @if (session('status'))
                    <div class="alert alert-success rounded-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger rounded-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" class="form-control rounded-3 @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn w-100 rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Email Password Reset Link</button>
                </form>
                <p class="text-center small mt-4 mb-0"><a class="text-success text-decoration-none" href="{{ route('login') }}">Back to Sign In</a></p>
            </div>
        </div>
    </div>
</main>
@endsection
