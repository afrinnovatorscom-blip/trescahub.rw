@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 640px;">
    <h1 class="h3 mb-3">Track an order</h1>
    <p class="text-muted">Enter the order reference and the phone number or email used at checkout.</p>
    @include('marketplace.partials.flash')
    <form method="POST" action="{{ route('track-order.submit') }}" class="row g-3">
        @csrf
        <div class="col-12"><label class="form-label" for="reference">Order reference</label><input id="reference" name="reference" class="form-control" required value="{{ old('reference') }}"></div>
        <div class="col-12"><label class="form-label" for="verification">Phone or email</label><input id="verification" name="verification" class="form-control" required value="{{ old('verification') }}"></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">View progress</button></div>
    </form>
</div></main>
@endsection
