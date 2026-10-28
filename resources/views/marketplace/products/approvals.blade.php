@extends('front.layouts.admin')
@section('page-title', 'Approvals')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3 mb-3">Product approval queue</h1>
    <div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Initial Stock</th>@if(auth()->user()->hasPermission('vendors.view'))<th>Vendor</th>@endif<th>Submitted</th><th></th></tr></thead>
        <tbody>
        @forelse($products as $product)
            <tr>
                <td><a href="{{ route('admin.shop-products.show', $product) }}">{{ $product->product_name }}</a><div class="small text-muted">{{ $product->description }}</div></td>
                <td>{{ $product->category->name ?? '—' }}</td>
                <td>{{ number_format($product->price, 0, '.', ',') }}</td>
                <td>
                    @if($product->tracks_inventory && array_key_exists('initial_stock_quantity', $product->pending_payload ?? []))
                        {{ number_format((int) $product->pending_payload['initial_stock_quantity']) }} {{ $product->stock_unit ?: 'units' }}
                    @else
                        —
                    @endif
                </td>
                @if(auth()->user()->hasPermission('vendors.view'))<td>{{ $product->vendor?->business_name ?? '—' }}</td>@endif
                <td>{{ $product->submitter->name ?? '—' }}<div class="small">{{ $product->submitted_at }}</div></td>
                <td><a class="btn btn-sm btn-dark" href="{{ route('admin.shop-products.show', $product) }}">Review</a></td>
            </tr>
        @empty
            <tr><td colspan="{{ auth()->user()->hasPermission('vendors.view') ? 7 : 6 }}" class="text-muted">Nothing is waiting for review.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $products->links() }}
</div>
@endsection
