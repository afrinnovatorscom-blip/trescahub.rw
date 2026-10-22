@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <h1 class="h3">Order {{ $tracking['reference'] }}</h1>
    @include('marketplace.partials.flash')
    @include('front.pages.partials.tracking-timeline')
    @if($canCancel)
        <form method="POST" action="{{ route('account.orders.cancel', $tracking['reference']) }}" class="mt-4">
            @csrf
            <label class="form-label" for="reason">Request cancellation</label>
            <input id="reason" name="reason" class="form-control mb-2" required>
            <button class="btn btn-outline-danger" type="submit" onclick="return confirm('Request cancellation?')">Send request</button>
        </form>
    @endif
</div></main>
@endsection
