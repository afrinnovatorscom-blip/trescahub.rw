@extends('front.layouts.admin')
@section('page-title', $record->reference)
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $record->reference }}</h1>
    <p>{{ $record->customer_name }} · {{ $record->phone }} · {{ $record->product_name }} × {{ $record->quantity }}</p>
    <p>Status: {{ str_replace('_',' ',$record->status) }}</p>
    <p>Customer message: {{ $record->customer_message }}</p>
    @if(auth()->user()->hasPermission('locations.view'))
        <p>Location: {{ $record->location->warehouse_name ?? ($record->product?->locations->first()->warehouse_name ?? 'Not recorded') }}</p>
    @endif
    @if(auth()->user()->hasPermission('vendors.view'))
        <p>Vendor: {{ $record->vendor?->business_name ?? 'Unassigned' }}</p>
        @if(auth()->user()->hasPermission('vendors.view_contacts') && $record->vendor)
            <p class="small">{{ $record->vendor?->phone }} {{ $record->vendor?->email }}</p>
        @endif
    @endif
    @if(auth()->user()->hasAnyPermission(['requests.allocate','requests.reassign']))
        <form method="POST" action="{{ route('marketplace.requests.allocate', $record) }}" class="row g-2 mb-3">@csrf
            <div class="col-md-4"><select name="vendor_id" class="form-select" required><option value="">Select vendor</option>@foreach($vendors as $vendor)<option value="{{ $vendor->id }}">{{ $vendor->business_name }}</option>@endforeach</select></div>
            <div class="col-md-3"><select name="product_location_id" class="form-select"><option value="">Location</option>@foreach($record->product?->locations ?? [] as $location)<option value="{{ $location->id }}">{{ $location->warehouse_name }}</option>@endforeach</select></div>
            <div class="col-auto"><button class="btn btn-dark" type="submit">Allocate</button></div>
        </form>
    @endif
    <form method="POST" action="{{ route('marketplace.requests.update', $record) }}" class="row g-2 mb-3">@csrf @method('PUT')
        <div class="col-md-2"><select name="priority" class="form-select">@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($record->priority===$priority)>{{ $priority }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="assigned_officer_id" class="form-select"><option value="">Officer</option>@foreach($officers as $officer)<option value="{{ $officer->id }}" @selected($record->assigned_officer_id==$officer->id)>{{ $officer->name }}</option>@endforeach</select></div>
        <div class="col-md-3"><input name="status" class="form-control" value="{{ $record->status }}" aria-label="Status"></div>
        <div class="col-md-4"><input name="customer_message" class="form-control" value="{{ $record->customer_message }}" placeholder="Customer-facing message"></div>
        <div class="col-12"><textarea name="internal_notes" class="form-control" placeholder="Internal notes">{{ $record->internal_notes }}</textarea></div>
        <div class="col-auto"><button class="btn btn-primary" type="submit">Save</button></div>
    </form>
    @if(auth()->user()->hasPermission('orders.manage') && !$record->order_id)
        <form method="POST" action="{{ route('marketplace.requests.convert', $record) }}">@csrf<button class="btn btn-success" type="submit" onclick="return confirm('Convert this request into an order?')">Convert to order</button></form>
    @endif
    <h2 class="h6 mt-4">History</h2>
    <ul>@foreach($record->histories as $history)<li>{{ $history->created_at }} · {{ $history->actor->name ?? 'Customer' }} · {{ $history->from_status }} → {{ $history->to_status }} @if($history->note)({{ $history->note }})@endif</li>@endforeach</ul>
</div>
@endsection
