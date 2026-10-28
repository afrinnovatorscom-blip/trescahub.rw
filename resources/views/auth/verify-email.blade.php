@extends('front.layouts.guest')

@section('title', 'Verify Email')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 560px;">
        <div class="text-center mb-4">@include('front.partials.auth-brand')</div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-2">Verify your email</h1>
                <p class="text-muted mb-4">Please verify your email address using the link we sent. If you did not receive it, request another below.</p>
                @if (session('status') === 'verification-link-sent')
                    <div class="alert alert-success rounded-3">A new verification link has been sent to your email address.</div>
                @endif
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button class="btn rounded-3 text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Resend verification email</button>
                    </form>
                    <div class="d-flex gap-3">
                        <a class="text-success text-decoration-none" href="{{ route('profile.show') }}">Edit profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-link p-0 text-success text-decoration-none" type="submit">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
