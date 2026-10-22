@extends('front.layouts.admin')
@section('page-title', 'Inventory')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Your inventory</h1>
    @forelse($products as $product)
        <form method="POST" action="{{ route('vendor.inventory.adjust', $product->slug) }}" class="row g-2 align-items-center mb-2">@csrf
            <div class="col-md-4">{{ $product->product_name }} <span class="text-muted">available {{ $product->availableQuantity() }}</span></div>
            <div class="col-md-2"><input name="delta" type="number" class="form-control" required></div>
            <div class="col-md-3"><input name="note" class="form-control" placeholder="Note"></div>
            <div class="col-auto"><button class="btn btn-dark" type="submit">Adjust</button></div>
        </form>
    @empty
        <p class="text-muted">No tracked stock on your products.</p>
    @endforelse
</div>
@endsection
