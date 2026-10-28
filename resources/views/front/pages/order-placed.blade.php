@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <h1 class="h3">Order received</h1>
    @include('marketplace.partials.flash')
    <p>Your reference is <strong>{{ $tracking['reference'] }}</strong>. Track it with this reference and the phone or email used at checkout.</p>
    <a class="btn btn-outline-dark" href="{{ route('track-order') }}">Track order</a>
    @include('front.pages.partials.tracking-timeline')
</div></main>
@endsection
