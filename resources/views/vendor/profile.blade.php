@extends('front.layouts.admin')
@section('page-title', 'Profile')
@section('content')
<div class="container py-4" style="max-width: 720px;">
    @include('marketplace.partials.flash')
    <h1 class="h3">{{ $vendor->business_name }}</h1>
    <p>Status: {{ ucfirst($vendor->status) }}</p>
    <p>Reference: {{ $vendor->reference_number ?: '—' }}</p>
    <p class="text-muted">Business details are maintained by TRESCA Hub. You can update the phone number on your login.</p>
    <form method="POST" action="{{ route('vendor.profile.update') }}">@csrf @method('PUT')
        <label class="form-label" for="phone">Your phone</label>
        <input id="phone" name="phone" class="form-control mb-3" value="{{ old('phone', auth()->user()->phone) }}">
        <button class="btn btn-primary" type="submit">Save</button>
    </form>
</div>
@endsection
