@extends('front.layouts.admin')
@section('page-title', 'Vendor')
@section('content')
<div class="container py-4" style="max-width: 860px;">
    @include('marketplace.partials.flash')
    <h1 class="h3 mb-3">{{ $vendor->exists ? 'Edit vendor' : 'Register vendor' }}</h1>
    <form method="POST" action="{{ $vendor->exists ? route('marketplace.vendors.update', $vendor) : route('marketplace.vendors.store') }}" class="row g-3">
        @csrf
        @if($vendor->exists) @method('PUT') @endif
        <div class="col-md-6"><label class="form-label">Business name</label><input name="business_name" class="form-control" required value="{{ old('business_name', $vendor->business_name) }}"></div>
        <div class="col-md-6"><label class="form-label">Legal name</label><input name="legal_name" class="form-control" value="{{ old('legal_name', $vendor->legal_name) }}"></div>
        <div class="col-md-4"><label class="form-label">Reference</label><input name="reference_number" class="form-control" value="{{ old('reference_number', $vendor->reference_number) }}"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="{{ old('email', $vendor->email) }}"></div>
        <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $vendor->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['pending','active','suspended','inactive'] as $status)<option value="{{ $status }}" @selected(old('status', $vendor->status)===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label">District</label><input name="district" class="form-control" value="{{ old('district', $vendor->district) }}"></div>
        <div class="col-md-4"><label class="form-label">Operating location</label><input name="operating_location" class="form-control" value="{{ old('operating_location', $vendor->operating_location) }}"></div>
        <div class="col-12"><label class="form-label">Address notes</label><textarea name="address_notes" class="form-control">{{ old('address_notes', $vendor->address_notes) }}</textarea></div>
        <div class="col-12"><label class="form-label">Internal notes</label><textarea name="internal_notes" class="form-control">{{ old('internal_notes', $vendor->internal_notes) }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary" type="submit">Save vendor</button></div>
    </form>
</div>
@endsection
