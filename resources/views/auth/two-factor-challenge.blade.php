@extends('front.layouts.guest')

@section('title', 'Two-Factor Authentication')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 520px;">
        <div class="text-center mb-4">@include('front.partials.auth-brand')</div>
        <div class="card border-0 shadow-sm rounded-4" x-data="{ recovery: false }">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-2">Two-factor authentication</h1>
                <p class="text-muted mb-4" x-show="!recovery">Enter the authentication code from your authenticator app.</p>
                <p class="text-muted mb-4" x-cloak x-show="recovery">Enter one of your account recovery codes.</p>
                @if ($errors->any())
                    <div class="alert alert-danger rounded-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('two-factor.login') }}">
                    @csrf
                    <div class="mb-4" x-show="!recovery">
                        <label for="code" class="form-label fw-semibold">Authentication code</label>
                        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="form-control rounded-3" x-ref="code" autofocus>
                    </div>
                    <div class="mb-4" x-cloak x-show="recovery">
                        <label for="recovery_code" class="form-label fw-semibold">Recovery code</label>
                        <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code" class="form-control rounded-3" x-ref="recovery">
                    </div>
                    <button class="btn w-100 rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Sign In</button>
                </form>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-link text-success text-decoration-none" x-show="!recovery" @click="recovery = true; $nextTick(() => $refs.recovery.focus())">Use a recovery code</button>
                    <button type="button" class="btn btn-link text-success text-decoration-none" x-cloak x-show="recovery" @click="recovery = false; $nextTick(() => $refs.code.focus())">Use an authentication code</button>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
