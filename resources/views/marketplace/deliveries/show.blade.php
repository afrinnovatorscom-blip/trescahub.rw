@extends('front.layouts.admin')
@section('page-title', $task->reference)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Delivery {{ $task->reference }}</h1>
    <p>Order {{ $task->order?->reference ?? 'Not recorded' }} · {{ $deliveryStatusLabel }}</p>

    <section class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Order and shipment details</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Order number</dt><dd class="col-sm-9">{{ $task->order?->reference ?? 'Not recorded' }}</dd>
                <dt class="col-sm-3">Product</dt><dd class="col-sm-9">{{ $task->item?->product_name ?? 'No order item is linked to this delivery' }}</dd>
                <dt class="col-sm-3">Quantity</dt><dd class="col-sm-9">{{ $task->item?->quantity ?? 'Not recorded' }}</dd>
                <dt class="col-sm-3">Vendor</dt><dd class="col-sm-9">{{ $task->vendor?->business_name ?? 'Not assigned' }}</dd>
                <dt class="col-sm-3">Delivery address</dt><dd class="col-sm-9">{{ $task->order?->delivery_address ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3">Delivery status</dt><dd class="col-sm-9">{{ $deliveryStatusLabel }}</dd>
                <dt class="col-sm-3">Order status</dt><dd class="col-sm-9">{{ $task->order?->fulfillment_status ?? 'Not recorded' }}</dd>
                <dt class="col-sm-3">Assigned officer</dt><dd class="col-sm-9">{{ $task->officer?->name ?? 'Not assigned' }}</dd>
                <dt class="col-sm-3">Customer update</dt><dd class="col-sm-9">{{ $task->customer_message ?: 'Not recorded' }}</dd>
            </dl>
        </div>
    </section>

    <section class="card mb-3">
        <div class="card-body">
            <h2 class="h5">Preparation details</h2>
            <dl class="row mb-0">
                <dt class="col-sm-3">Delivery method</dt><dd class="col-sm-9">{{ $task->delivery_method ? (config('marketplace.delivery_methods.'.$task->delivery_method) ?? $task->delivery_method) : 'Not recorded' }}</dd>
                <dt class="col-sm-3">Courier</dt><dd class="col-sm-9">{{ $task->courier_name ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3">Courier phone</dt><dd class="col-sm-9">{{ $task->delivery_phone ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3">Vehicle registration</dt><dd class="col-sm-9">{{ $task->vehicle_registration ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3">Expected delivery</dt><dd class="col-sm-9">{{ $task->expected_delivery_at?->format('M j, Y') ?? 'Not scheduled' }}</dd>
                <dt class="col-sm-3">Tracking reference</dt><dd class="col-sm-9">{{ $task->tracking_reference ?: 'Not recorded' }}</dd>
                <dt class="col-sm-3">Delivery notes</dt><dd class="col-sm-9">{{ $task->delivery_notes ?: 'None' }}</dd>
                @if($task->dispatch_proof_path)
                    <dt class="col-sm-3">Dispatch proof</dt><dd class="col-sm-9"><a href="{{ route('marketplace.deliveries.dispatch-proof', $task) }}">Download dispatch proof</a></dd>
                @endif
                @if($task->proof_path)
                    <dt class="col-sm-3">Proof of delivery</dt><dd class="col-sm-9"><a href="{{ route('marketplace.deliveries.proof.download', $task) }}">Download proof of delivery</a></dd>
                @endif
            </dl>
        </div>
    </section>

    @if(auth()->user()->hasPermission('locations.view'))
        <p>Pickup: {{ $task->location?->warehouse_name ?? 'Not recorded' }} {{ $task->location?->shelf_reference ?? '' }}</p>
    @endif
    @if($task->internal_notes)<p>Internal: {{ $task->internal_notes }}</p>@endif

    @if($deliveryStatuses)
        <form method="POST" action="{{ route('marketplace.deliveries.update', $task) }}" class="row g-2 mb-3">@csrf
            <div class="col-md-3">
                <select name="order_status" class="form-select" required>
                    @foreach($deliveryStatuses as $deliveryStatus)
                        <option value="{{ $deliveryStatus }}">{{ \Illuminate\Support\Str::headline($deliveryStatus) }}</option>
                    @endforeach
                </select>
                @error('order_status')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4"><input name="customer_message" class="form-control" placeholder="Customer update" value="{{ old('customer_message') }}"></div>
            <div class="col-md-4"><input name="internal_notes" class="form-control" placeholder="Internal note" value="{{ old('internal_notes') }}"></div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Save milestone</button></div>
        </form>
    @else
        <p class="text-muted">No delivery milestone is available from the current order status.</p>
    @endif

    <form method="POST" action="{{ route('marketplace.deliveries.attempt', $task) }}" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><select name="result" class="form-select"><option value="failed">Failed</option><option value="rescheduled">Rescheduled</option></select></div>
        <div class="col-md-4"><input name="reason" class="form-control" placeholder="Reason" required></div>
        <div class="col-auto"><button class="btn btn-outline-danger" type="submit">Record attempt</button></div>
    </form>
    <form method="POST" action="{{ route('marketplace.deliveries.proof', $task) }}" enctype="multipart/form-data" class="row g-2 mb-3">@csrf
        <div class="col-md-4"><input type="file" name="proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required></div>
        <div class="col-auto"><button class="btn btn-outline-dark" type="submit">Upload proof</button></div>
    </form>
    <form method="POST" action="{{ route('marketplace.deliveries.reassign', $task) }}" class="row g-2">@csrf
        <div class="col-md-4">
            <select name="delivery_officer_id" class="form-select">
                @foreach($officers as $officer)
                    <option value="{{ $officer->id }}" @selected((int) $task->delivery_officer_id === (int) $officer->id)>{{ $officer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto"><button class="btn btn-dark" type="submit">Reassign</button></div>
    </form>
    <h2 class="h6 mt-4">Attempts</h2>
    <ul>
        @forelse($task->attempts as $attempt)
            <li>{{ $attempt->attempted_at }} · {{ $attempt->result }} · {{ $attempt->reason ?: 'No reason recorded' }} · {{ $attempt->actor?->name ?? 'Unknown user' }}</li>
        @empty
            <li class="text-muted">No attempts.</li>
        @endforelse
    </ul>
</div>
@endsection
