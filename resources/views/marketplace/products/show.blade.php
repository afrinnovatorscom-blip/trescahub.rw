@extends('front.layouts.admin')
@section('page-title', 'Product')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $product->product_name }}</h1>
    <p>
        <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $product->approval_status) }}</span>
        <span class="badge bg-secondary">{{ $product->publication_status }}</span>
        @if($product->revision_status)
            <span class="badge bg-warning text-dark">Revision {{ str_replace('_', ' ', $product->revision_status) }}</span>
        @endif
    </p>
    <p>RWF {{ number_format($product->price, 0, '.', ',') }} · {{ $product->working_days }} working days · Current stock {{ number_format($product->stock_quantity) }} {{ $product->stock_unit ?: 'units' }}</p>
    @if($product->tracks_inventory && array_key_exists('initial_stock_quantity', $product->pending_payload ?? []))
        <p class="alert alert-info py-2">
            Unapproved initial stock: {{ number_format((int) $product->pending_payload['initial_stock_quantity']) }} {{ $product->stock_unit ?: 'units' }}.
            It remains unavailable until the product is approved.
        </p>
    @endif
    @if(auth()->user()->hasPermission('vendors.view'))
        <p>Vendor: {{ $product->vendor?->business_name ?? 'Not assigned' }}</p>
    @endif
    @if(auth()->user()->hasPermission('finance.view') && $product->acquisition_cost)
        <p>Acquisition cost: RWF {{ number_format($product->acquisition_cost, 0, '.', ',') }}</p>
    @endif
    @if($product->internal_notes)
        <p class="text-muted">{{ $product->internal_notes }}</p>
    @endif
    @if($product->pending_payload)
        <div class="alert alert-warning">Unreviewed customer-facing changes are waiting. The public listing still shows the last approved version.</div>
    @endif
    <div class="d-flex flex-wrap gap-2 mb-4">
        @if(auth()->user()->hasPermission('products.approve'))
            <form method="POST" action="{{ route('marketplace.products.approve', $product) }}">@csrf<button class="btn btn-success btn-sm" type="submit">Approve</button></form>
            <form method="POST" action="{{ route('marketplace.products.reject', $product) }}" class="d-flex gap-2">@csrf<input name="note" class="form-control form-control-sm" placeholder="Rejection note" required><button class="btn btn-danger btn-sm" type="submit">Reject</button></form>
            <form method="POST" action="{{ route('marketplace.products.changes', $product) }}" class="d-flex gap-2">@csrf<input name="note" class="form-control form-control-sm" placeholder="Changes requested" required><button class="btn btn-warning btn-sm" type="submit">Request changes</button></form>
        @endif
        @if(auth()->user()->hasPermission('products.publish'))
            <form method="POST" action="{{ route('marketplace.products.publish', $product) }}">@csrf<button class="btn btn-primary btn-sm" type="submit">Publish</button></form>
            <form method="POST" action="{{ route('marketplace.products.unpublish', $product) }}">@csrf<button class="btn btn-outline-secondary btn-sm" type="submit">Unpublish</button></form>
            <form method="POST" action="{{ route('marketplace.products.archive', $product) }}">@csrf<button class="btn btn-outline-dark btn-sm" type="submit" onclick="return confirm('Archive this product?')">Archive</button></form>
        @endif
    </div>
    @if(auth()->user()->hasPermission('products.assign_vendor'))
        <form method="POST" action="{{ route('marketplace.products.vendor', $product) }}" class="row g-2 mb-4">
            @csrf
            <div class="col-md-6">
                <select name="vendor_id" class="form-select">
                    <option value="">No vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected($product->vendor_id == $vendor->id)>{{ $vendor->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-dark" type="submit">Assign vendor</button></div>
        </form>
    @endif
    @if(auth()->user()->hasPermission('locations.view'))
        <h2 class="h5">Locations</h2>
        <ul>
            @forelse($product->locations as $location)
                <li>{{ $location->warehouse_name }} @if($location->district) · {{ $location->district }} @endif @if($location->shelf_reference) · {{ $location->shelf_reference }} @endif — {{ $location->quantity }} on hand</li>
            @empty
                <li class="text-muted">No storage location recorded yet.</li>
            @endforelse
        </ul>
    @endif
    @if(auth()->user()->hasPermission('locations.manage'))
        <form method="POST" action="{{ route('marketplace.locations.store', $product) }}" class="row g-2 mb-4">
            @csrf
            <div class="col-md-3"><input name="warehouse_name" class="form-control" placeholder="Warehouse" required></div>
            <div class="col-md-2"><input name="district" class="form-control" placeholder="District"></div>
            <div class="col-md-2"><input name="shelf_reference" class="form-control" placeholder="Shelf / bin"></div>
            <div class="col-md-2"><input name="quantity" type="number" min="0" class="form-control" placeholder="Qty"></div>
            <div class="col-auto"><button class="btn btn-outline-dark" type="submit">Add location</button></div>
        </form>
    @endif
    @if(auth()->user()->hasPermission('inventory.manage'))
        <form method="POST" action="{{ route('marketplace.inventory.adjust', $product) }}" class="row g-2 mb-4">
            @csrf
            <div class="col-md-2"><input name="delta" type="number" class="form-control" placeholder="+/- quantity" required></div>
            <div class="col-md-4"><input name="note" class="form-control" placeholder="Adjustment note"></div>
            <div class="col-auto"><button class="btn btn-outline-primary" type="submit">Adjust stock</button></div>
        </form>
    @endif
    <h2 class="h6">Review history</h2>
    <ul class="list-unstyled">
        @foreach($product->approvalHistories as $history)
            <li class="mb-1"><strong>{{ $history->action }}</strong> · {{ $history->actor->name ?? 'System' }} · {{ $history->created_at }} @if($history->note)— {{ $history->note }}@endif</li>
        @endforeach
    </ul>
</div>
@endsection
