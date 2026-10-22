@extends('front.layouts.admin')
@section('page-title', 'Inventory')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="d-flex justify-content-between"><h1 class="h3">Inventory</h1><a href="{{ route('marketplace.inventory.index', ['low' => 1]) }}">Low stock</a></div>
    <table class="table"><thead><tr><th>Product</th><th>On hand</th><th>Reserved</th><th>Available</th><th></th></tr></thead>
        <tbody>@forelse($products as $product)<tr>
            <td>{{ $product->product_name }}</td><td>{{ $product->stock_quantity }}</td><td>{{ $product->reserved_quantity }}</td><td>{{ $product->availableQuantity() }}</td>
            <td>@if(auth()->user()->hasPermission('inventory.manage'))<form method="POST" action="{{ route('marketplace.inventory.adjust', $product) }}" class="d-flex gap-1">@csrf<input name="delta" type="number" class="form-control form-control-sm" style="width:90px" required><button class="btn btn-sm btn-dark">Adjust</button></form>@endif</td>
        </tr>@empty<tr><td colspan="5" class="text-muted">No tracked products.</td></tr>@endforelse</tbody></table>
    <h2 class="h6">Recent movements</h2>
    <ul>@foreach($movements as $movement)<li>{{ $movement->created_at }} · {{ $movement->product->product_name ?? 'Product' }} · {{ $movement->type }} {{ $movement->quantity_delta }}</li>@endforeach</ul>
</div>
@endsection
