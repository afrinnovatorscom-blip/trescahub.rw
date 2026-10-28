@extends('front.layouts.guest')

@section('title', 'Confirm Password')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 520px;">
        <div class="text-center mb-4">@include('front.partials.auth-brand')</div>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 p-md-5">
                <h1 class="h4 fw-bold mb-2">Confirm your password</h1>
                <p class="text-muted mb-4">Please confirm your password before continuing to this secure area.</p>
                @if ($errors->any())
                    <div class="alert alert-danger rounded-3">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('password.confirm') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input id="password" name="password" type="password" class="form-control rounded-3" required autofocus autocomplete="current-password">
                    </div>
                    <button class="btn w-100 rounded-3 py-2 fw-semibold text-white" style="background:#16a34a;border-color:#16a34a;" type="submit">Confirm</button>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
