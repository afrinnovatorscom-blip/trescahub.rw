@extends('front.layouts.admin')
@section('page-title', $record->reference)
@section('content')
<div class="container py-4" style="max-width: 760px;">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $record->reference }}</h1>
    <p>{{ $record->product_name }} × {{ $record->quantity }}</p>
    <p>{{ $record->delivery_location }}</p>
    <p>{{ $record->instructions }}</p>
    <p>Customer: {{ $record->customer_name }} · {{ $record->phone }}</p>
    @if($record->status === 'awaiting_vendor_response')
        <form method="POST" action="{{ route('vendor.requests.respond', $record->reference) }}" class="row g-2">@csrf
            <div class="col-md-4"><select name="decision" class="form-select"><option value="accept">Accept</option><option value="decline">Decline</option><option value="clarification">Need clarification</option></select></div>
            <div class="col-md-6"><input name="note" class="form-control" placeholder="Internal note"></div>
            <div class="col-auto"><button class="btn btn-primary" type="submit">Send</button></div>
        </form>
    @else
        <p class="text-muted">This request is {{ str_replace('_',' ',$record->status) }}.</p>
    @endif
</div>
@endsection
