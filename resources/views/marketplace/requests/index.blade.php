@extends('front.layouts.admin')
@section('page-title', 'Requests')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <h1 class="h3">Product requests</h1>
    <form class="row g-2 my-3"><div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Reference"></div><div class="col-md-3"><input name="status" value="{{ request('status') }}" class="form-control" placeholder="Status"></div><div class="col-auto"><button class="btn btn-dark">Filter</button></div></form>
    <table class="table"><thead><tr><th>Reference</th><th>Product</th><th>Status</th>@if(auth()->user()->hasPermission('vendors.view'))<th>Vendor</th>@endif<th></th></tr></thead>
        <tbody>@forelse($requests as $record)<tr><td>{{ $record->reference }}</td><td>{{ $record->product_name }}</td><td>{{ str_replace('_',' ',$record->status) }}</td>@if(auth()->user()->hasPermission('vendors.view'))<td>{{ $record->vendor?->business_name ?? 'Unassigned' }}</td>@endif<td><a href="{{ route('marketplace.requests.show', $record) }}">Open</a></td></tr>@empty<tr><td colspan="5" class="text-muted">No requests.</td></tr>@endforelse</tbody></table>
    {{ $requests->links() }}
</div>
@endsection
