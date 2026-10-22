@extends('front.layouts.admin')
@section('page-title', $order->reference)
@section('content')
<div class="container py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $order->reference }}</h1>
    <p>{{ $order->customer_name }} · {{ $order->phone }}</p>
    <p>{{ $order->delivery_address }}</p>
    @foreach($items as $item)
        <div class="border rounded p-3 mb-3">
            <strong>{{ $item->product_name }}</strong> × {{ $item->quantity }}
            @if($item->location)<div class="small">Pickup: {{ $item->location->warehouse_name }} {{ $item->location->shelf_reference }}</div>@endif
            <form method="POST" action="{{ route('vendor.orders.respond', $item) }}" class="row g-2 mt-2">@csrf
                <div class="col-md-3"><select name="decision" class="form-select"><option value="accept">Accept</option><option value="decline">Decline</option><option value="clarification">Clarification</option></select></div>
                <div class="col-md-5"><input name="note" class="form-control" placeholder="Note"></div>
                <div class="col-auto"><button class="btn btn-outline-dark" type="submit">Respond</button></div>
            </form>
            <form method="POST" action="{{ route('vendor.orders.progress', $item) }}" class="row g-2 mt-2">@csrf
                <div class="col-md-4"><select name="fulfillment_status" class="form-select"><option value="processing">Processing</option><option value="awaiting_stock">Awaiting stock</option><option value="ready_for_dispatch">Ready for dispatch</option></select></div>
                <div class="col-auto"><button class="btn btn-primary" type="submit">Update fulfillment</button></div>
            </form>
        </div>
    @endforeach
</div>
@endsection
