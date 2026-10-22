@extends('front.layouts.admin')
@section('page-title', $order->reference)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $order->reference }}</h1>
    <p>{{ $order->customer_name }} · {{ $order->phone }} · {{ $order->delivery_address }}</p>
    <p>Fulfillment: {{ str_replace('_',' ',$order->fulfillment_status) }} · Payment: {{ $order->payment_status }}</p>
    <p>Customer update: {{ $order->customer_status_message }}</p>
    @if(auth()->user()->hasPermission('orders.manage'))
        <form method="POST" action="{{ route('marketplace.orders.status', $order) }}" class="row g-2 mb-3">@csrf
            <div class="col-md-3"><input name="fulfillment_status" class="form-control" value="{{ $order->fulfillment_status }}" required></div>
            <div class="col-md-4"><input name="customer_message" class="form-control" placeholder="Customer-facing update"></div>
            <div class="col-md-3"><input name="note" class="form-control" placeholder="Internal note"></div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Update status</button></div>
        </form>
        <form method="POST" action="{{ route('marketplace.orders.payment', $order) }}" class="row g-2 mb-3">@csrf
            <div class="col-md-3"><select name="payment_status" class="form-select">@foreach(['pending','paid','failed','refunded'] as $status)<option value="{{ $status }}" @selected($order->payment_status===$status)>{{ $status }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-outline-dark" type="submit">Update payment</button></div>
        </form>
    @endif
    <table class="table"><thead><tr><th>Item</th><th>Qty</th><th>Price</th>@if(auth()->user()->hasPermission('vendors.view'))<th>Vendor</th>@endif @if(auth()->user()->hasPermission('locations.view'))<th>Location</th>@endif<th>Assignment</th><th></th></tr></thead>
        <tbody>@foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format($item->unit_price, 0, '.', ',') }}</td>
                @if(auth()->user()->hasPermission('vendors.view'))<td>{{ $item->vendor?->business_name ?? '—' }}</td>@endif
                @if(auth()->user()->hasPermission('locations.view'))<td>{{ $item->location->warehouse_name ?? '—' }}</td>@endif
                <td>{{ $item->assignment_status }}</td>
                <td>
                    @if(auth()->user()->hasPermission('orders.manage'))
                        <form method="POST" action="{{ route('marketplace.orders.allocate-item', $item) }}" class="d-flex gap-1">@csrf
                            <select name="vendor_id" class="form-select form-select-sm">@foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected($item->vendor_id==$vendor->id)>{{ $vendor->business_name }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-dark" type="submit">Allocate</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach</tbody></table>
    <h2 class="h6">History</h2>
    <ul>@foreach($order->histories as $history)<li>{{ $history->created_at }} · {{ $history->from_status }} → {{ $history->to_status }} · {{ $history->customer_message }}</li>@endforeach</ul>
</div>
@endsection
