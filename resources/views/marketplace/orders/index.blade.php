@extends('front.layouts.admin')
@section('page-title', 'Orders')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Orders</h1>
    <form class="row g-2 my-3">
        <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference"></div>
        <div class="col-md-3"><input name="status" value="{{ request('status') }}" class="form-control" placeholder="Fulfillment status"></div>
        @if(auth()->user()->hasPermission('vendors.view'))
            <div class="col-md-3"><select name="vendor" class="form-select"><option value="">All vendors</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(request('vendor')==$vendor->id)>{{ $vendor->business_name }}</option>@endforeach</select></div>
        @endif
        <div class="col-auto"><button class="btn btn-dark">Filter</button></div>
    </form>
    <table class="table"><thead><tr><th>Reference</th><th>Customer</th><th>Fulfillment</th><th>Payment</th><th>Total</th></tr></thead>
        <tbody>@forelse($orders as $order)<tr><td><a href="{{ route('marketplace.orders.show', $order) }}">{{ $order->reference }}</a></td><td>{{ $order->customer_name }}</td><td>{{ str_replace('_',' ',$order->fulfillment_status) }}</td><td>{{ $order->payment_status }}</td><td>{{ number_format($order->total, 0, '.', ',') }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No orders.</td></tr>@endforelse</tbody></table>
    {{ $orders->links() }}
</div>
@endsection
