@extends('front.layouts.guest')

@section('title', 'Choose a New Password')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 520px;">
        <div class="text-center mb-4">@include('front.partials.auth-brand')</div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-4">Choose a new password</h1>
                @if ($errors->any())
                    <div class="alert alert-danger rounded-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" class="form-control rounded-3 @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New password</label>
                        <input id="password" name="password" type="password" class="form-control rounded-3 @error('password') is-invalid @enderror" required autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control rounded-3" required autocomplete="new-password">
                    </div>
                    <button class="btn w-100 rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
