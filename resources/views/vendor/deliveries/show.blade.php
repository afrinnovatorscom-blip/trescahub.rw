@extends('front.layouts.admin')
@section('page-title', 'Delivery '.$task->reference)
@section('content')
<div class="container py-4">
    <a href="{{ route('vendor.deliveries.index') }}" class="text-decoration-none">&larr; Back to deliveries</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Delivery {{ $task->reference }}</h1>
            <div class="text-muted">Order {{ $task->order?->reference ?? '—' }}</div>
        </div>
        <span class="badge text-bg-light border fs-6">{{ \Illuminate\Support\Str::headline($task->status) }}</span>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Preparation details</h2>
                    <dl class="row">
                        <dt class="col-sm-5">Delivery method</dt><dd class="col-sm-7">{{ $task->delivery_method ? (config('marketplace.delivery_methods.'.$task->delivery_method) ?? $task->delivery_method) : 'Not recorded' }}</dd>
                        <dt class="col-sm-5">Courier</dt><dd class="col-sm-7">{{ $task->courier_name ?: 'Not recorded' }}</dd>
                        <dt class="col-sm-5">Courier phone</dt><dd class="col-sm-7">{{ $task->delivery_phone ?: 'Not recorded' }}</dd>
                        <dt class="col-sm-5">Vehicle registration</dt><dd class="col-sm-7">{{ $task->vehicle_registration ?: 'Not recorded' }}</dd>
                        <dt class="col-sm-5">Expected delivery</dt><dd class="col-sm-7">{{ $task->expected_delivery_at?->format('M j, Y') ?? 'Not scheduled' }}</dd>
                        <dt class="col-sm-5">Tracking reference</dt><dd class="col-sm-7">{{ $task->tracking_reference ?: 'Not recorded' }}</dd>
                        <dt class="col-sm-5">Delivery notes</dt><dd class="col-sm-7">{{ $task->delivery_notes ?: 'None' }}</dd>
                        @if($task->dispatch_proof_path)
                            <dt class="col-sm-5">Dispatch proof</dt><dd class="col-sm-7"><a href="{{ route('vendor.deliveries.dispatch-proof', $task->reference) }}">Download dispatch proof</a></dd>
                        @endif
                    </dl>
                    <h2 class="h5">Assigned items</h2>
                    @forelse($items as $item)
                        <div class="py-2 border-bottom">
                            <div class="fw-semibold">{{ $item->product_name }} × {{ $item->quantity }}</div>
                            @if($item->fulfillment_instructions)
                                <div class="small text-muted mt-1">{{ $item->fulfillment_instructions }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No assigned item details are available.</p>
                    @endforelse
                    @if($task->fulfillment_instructions)
                        <h3 class="h6 mt-3">Delivery instructions</h3>
                        <p class="mb-0">{{ $task->fulfillment_instructions }}</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h5">Timeline</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Expected delivery</dt>
                        <dd class="col-sm-6">{{ $task->order?->delivery_estimate_at?->format('M j, Y') ?? 'Not scheduled' }}</dd>
                        <dt class="col-sm-6">Payment status</dt>
                        <dd class="col-sm-6">{{ \Illuminate\Support\Str::headline($task->order?->payment_status ?? 'unknown') }}</dd>
                        <dt class="col-sm-6">Dispatched</dt>
                        <dd class="col-sm-6">{{ $task->dispatched_at?->format('M j, Y g:i A') ?? 'Not dispatched' }}</dd>
                        <dt class="col-sm-6">Delivered</dt>
                        <dd class="col-sm-6">{{ $task->delivered_at?->format('M j, Y g:i A') ?? 'Not delivered' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
