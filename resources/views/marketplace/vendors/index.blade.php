@extends('front.layouts.admin')
@section('page-title', 'Vendors')
@section('content')
<div class="container-fluid py-4">
    @include('marketplace.partials.flash')
    <div class="d-flex justify-content-between mb-3"><h1 class="h3">Vendors</h1>@if(auth()->user()->hasPermission('vendors.create'))<a class="btn btn-primary" href="{{ route('marketplace.vendors.create') }}">Register vendor</a>@endif</div>
    <form class="row g-2 mb-3"><div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search business name"></div><div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div><div class="col-auto"><button class="btn btn-dark">Filter</button></div></form>
    <table class="table"><thead><tr><th>Business</th><th>Status</th><th>Products</th><th></th></tr></thead><tbody>
        @forelse($vendors as $vendor)
            <tr><td>{{ $vendor->business_name }}</td><td>{{ ucfirst($vendor->status) }}</td><td>{{ $vendor->products_count }}</td><td><a href="{{ route('marketplace.vendors.show', $vendor) }}">Open</a></td></tr>
        @empty
            <tr><td colspan="4" class="text-muted">No vendors yet.</td></tr>
        @endforelse
    </tbody></table>
    {{ $vendors->links() }}
</div>
@endsection
