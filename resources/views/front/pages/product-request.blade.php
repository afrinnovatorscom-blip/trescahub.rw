@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container" style="max-width: 760px;">
    <h1 class="h3 mb-3">Request a product</h1>
    @include('marketplace.partials.flash')
    <form method="POST" action="{{ route('product-requests.store') }}" class="row g-3">
        @csrf
        @if($product)
            <input type="hidden" name="product_slug" value="{{ $product->slug }}">
            <div class="col-12"><div class="alert alert-light">Requesting <strong>{{ $product->product_name }}</strong></div></div>
        @else
            <div class="col-12"><label class="form-label" for="product_name">Product you need</label><input id="product_name" name="product_name" class="form-control" value="{{ old('product_name') }}"></div>
        @endif
        <div class="col-md-6"><label class="form-label" for="customer_name">Name</label><input id="customer_name" name="customer_name" class="form-control" required value="{{ old('customer_name', auth()->user()->name ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input id="phone" name="phone" class="form-control" required value="{{ old('phone') }}"></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email', auth()->user()->email ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label" for="quantity">Quantity</label><input id="quantity" type="number" min="1" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" required></div>
        <div class="col-12"><label class="form-label" for="delivery_location">Delivery location</label><input id="delivery_location" name="delivery_location" class="form-control" value="{{ old('delivery_location') }}"></div>
        <div class="col-12"><label class="form-label" for="instructions">Instructions</label><textarea id="instructions" name="instructions" class="form-control">{{ old('instructions') }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Submit request</button></div>
    </form>
</div></main>
@endsection
