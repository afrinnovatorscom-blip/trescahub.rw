@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <h1 class="h3">Order {{ $tracking['reference'] }}</h1>
    @include('front.pages.partials.tracking-timeline')
</div></main>
@endsection
