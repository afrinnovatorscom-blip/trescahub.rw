@extends('front.layouts.admin')
@section('page-title', 'Requests')
@section('content')
<div class="container-fluid py-4">
    <h1 class="h3">Assigned requests</h1>
    <table class="table"><thead><tr><th>Reference</th><th>Product</th><th>Qty</th><th>Status</th></tr></thead>
        <tbody>@forelse($requests as $record)<tr><td><a href="{{ route('vendor.requests.show', $record->reference) }}">{{ $record->reference }}</a></td><td>{{ $record->product_name }}</td><td>{{ $record->quantity }}</td><td>{{ str_replace('_',' ',$record->status) }}</td></tr>@empty<tr><td colspan="4" class="text-muted">No requests assigned to you.</td></tr>@endforelse</tbody></table>
</div>
@endsection
