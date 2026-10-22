@extends('front.layouts.admin')
@section('page-title', $task->reference)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Delivery {{ $task->reference }}</h1>
    <p>Order {{ $task->order->reference }} · {{ $task->status }}</p>
    @if(auth()->user()->hasPermission('locations.view'))
        <p>Pickup: {{ $task->location->warehouse_name ?? 'Not recorded' }} {{ $task->location->shelf_reference ?? '' }}</p>
    @endif
    @if($task->internal_notes)<p>Internal: {{ $task->internal_notes }}</p>@endif
    <form method="POST" action="{{ route('marketplace.deliveries.update', $task) }}" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><select name="order_status" class="form-select"><option value="dispatched">Dispatched</option><option value="out_for_delivery">Out for delivery</option><option value="delivered">Delivered</option></select></div>
        <div class="col-md-4"><input name="customer_message" class="form-control" placeholder="Customer update"></div>
        <div class="col-md-4"><input name="internal_notes" class="form-control" placeholder="Internal note"></div>
        <div class="col-auto"><button class="btn btn-primary" type="submit">Save milestone</button></div>
    </form>
    <form method="POST" action="{{ route('marketplace.deliveries.attempt', $task) }}" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><select name="result" class="form-select"><option value="failed">Failed</option><option value="rescheduled">Rescheduled</option></select></div>
        <div class="col-md-4"><input name="reason" class="form-control" placeholder="Reason" required></div>
        <div class="col-auto"><button class="btn btn-outline-danger" type="submit">Record attempt</button></div>
    </form>
    <form method="POST" action="{{ route('marketplace.deliveries.proof', $task) }}" enctype="multipart/form-data" class="row g-2 mb-3">@csrf
        <div class="col-md-4"><input type="file" name="proof" class="form-control" required></div>
        <div class="col-auto"><button class="btn btn-outline-dark" type="submit">Upload proof</button></div>
    </form>
    <form method="POST" action="{{ route('marketplace.deliveries.reassign', $task) }}" class="row g-2">@csrf
        <div class="col-md-4"><select name="delivery_officer_id" class="form-select">@foreach($officers as $officer)<option value="{{ $officer->id }}">{{ $officer->name }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-dark" type="submit">Reassign</button></div>
    </form>
    <h2 class="h6 mt-4">Attempts</h2>
    <ul>@forelse($task->attempts as $attempt)<li>{{ $attempt->attempted_at }} · {{ $attempt->result }} · {{ $attempt->reason }}</li>@empty<li class="text-muted">No attempts.</li>@endforelse</ul>
</div>
@endsection
