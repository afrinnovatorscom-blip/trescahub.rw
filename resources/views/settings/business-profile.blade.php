@extends('front.layouts.admin')
@section('title', 'Business Profile — Settings')
@section('page-title', 'Business Profile')

@push('styles')
    @include('settings._styles')
@endpush

@section('content')
<div class="settings-page py-3">

    {{-- Back to Settings --}}
    <a href="{{ route('admin.settings.index') }}" class="settings-back-link">
        <i class="bi bi-arrow-left"></i> Back to Settings
    </a>

    {{-- Page Header --}}
    <div class="settings-header">
        <h1 class="settings-title">Business Profile</h1>
        <p class="settings-subtitle">Platform business identity, public branding, and contact information.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Platform Identity</h2>
            <p class="settings-card-desc">Active organization details and public storefront contact channels.</p>
        </div>

        <div class="settings-info-list mb-4">
            <div class="settings-info-row">
                <span class="settings-info-label">Platform Name</span>
                <span class="settings-info-val">{{ $appName }}</span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Platform Web Address</span>
                <span class="settings-info-val">
                    <a href="{{ $appUrl }}" target="_blank" class="text-decoration-none text-primary">{{ $appUrl }}</a>
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Inquiries & Support Email</span>
                <span class="settings-info-val">{{ $supportEmail }}</span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Support Phone Number</span>
                <span class="settings-info-val">{{ $contactPhone }}</span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Operating Location</span>
                <span class="settings-info-val">{{ $operatingLocation }}</span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Operating Organization</span>
                <span class="settings-info-val">{{ $organization }}</span>
            </div>
        </div>

        <div class="p-3 bg-light rounded-3 border">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-info-circle text-primary"></i>
                <span class="fw-semibold small text-dark">Configuration Scope</span>
            </div>
            <p class="text-muted small mb-0">
                Platform identity values are loaded from the verified environment and storefront template configuration. Individual vendor business profiles are managed under <a href="{{ route('marketplace.vendors.index') }}" class="text-primary text-decoration-none fw-medium">Vendors</a>.
            </p>
        </div>
    </div>

</div>
@endsection

