@extends('front.layouts.admin')
@section('page-title', 'Orders')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Assigned orders</h1>
    <table class="table"><thead><tr><th>Order</th><th>Item</th><th>Qty</th><th>Assignment</th><th>Fulfillment</th></tr></thead>
        <tbody>@forelse($items as $item)<tr>
            <td><a href="{{ route('vendor.orders.show', $item->order->reference) }}">{{ $item->order->reference }}</a></td>
            <td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td>{{ $item->assignment_status }}</td><td>{{ str_replace('_',' ',$item->fulfillment_status) }}</td>
        </tr>@empty<tr><td colspan="5" class="text-muted">No orders assigned.</td></tr>@endforelse</tbody></table>
</div>
@endsection
