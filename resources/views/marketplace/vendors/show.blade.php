@extends('front.layouts.admin')
@section('page-title', $vendor->business_name)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="d-flex justify-content-between"><h1 class="h3">{{ $vendor->business_name }}</h1><a href="{{ route('marketplace.vendors.edit', $vendor) }}">Edit</a></div>
    <p>Status: {{ ucfirst($vendor->status) }} · Reference: {{ $vendor->reference_number ?: '—' }}</p>
    @if(auth()->user()->hasPermission('vendors.view_contacts'))
        <p>Phone: {{ $vendor->phone ?: '—' }} · Email: {{ $vendor->email ?: '—' }}</p>
    @else
        <p class="text-muted">Contact details are hidden for your role.</p>
    @endif
    @if(auth()->user()->hasPermission('locations.view'))
        <p>Location: {{ $vendor->operating_location ?: '—' }} {{ $vendor->district }}</p>
    @endif
    @if(auth()->user()->hasPermission('vendors.manage'))
        <form method="POST" action="{{ route('marketplace.vendors.status', $vendor) }}" class="d-flex gap-2 mb-4">@csrf @method('PATCH')
            <select name="status" class="form-select" style="max-width:200px">@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected($vendor->status===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <button class="btn btn-dark" type="submit">Update status</button>
        </form>
    @endif
    <h2 class="h6">Products</h2>
    <ul>@forelse($vendor->products as $product)<li><a href="{{ route('admin.shop-products.show', $product) }}">{{ $product->product_name }}</a></li>@empty<li class="text-muted">No products assigned.</li>@endforelse</ul>
    <h2 class="h6">Requests</h2>
    <ul>@forelse($requests as $record)<li>{{ $record->reference }} — {{ $record->status }}</li>@empty<li class="text-muted">No requests.</li>@endforelse</ul>
    <h2 class="h6">Order items</h2>
    <ul>@forelse($items as $item)<li>{{ $item->order->reference ?? '' }} — {{ $item->product_name }} ({{ $item->assignment_status }})</li>@empty<li class="text-muted">No order items.</li>@endforelse</ul>
    <p><a href="{{ route('marketplace.users.create') }}">Add a vendor user</a></p>
</div>
@endsection
