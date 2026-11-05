@extends('front.layouts.admin')
@section('title', 'Vendors & Partners — Settings')
@section('page-title', 'Vendors & Partners')

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
        <h1 class="settings-title">Vendors & Partners</h1>
        <p class="settings-subtitle">Vendor account lifecycle, fulfillment transitions, and partner branding.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Vendor Workflow & Partnership Controls</h2>
            <p class="settings-card-desc">Fulfillment permissions and public branding authorization.</p>
        </div>

        <div class="settings-info-list mb-4">
            <div class="settings-info-row">
                <span class="settings-info-label">Vendor Account States</span>
                <span class="settings-info-val">
                    <span class="badge bg-success-subtle text-success border border-success-subtle me-1">Active</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle me-1">Under Review</span>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Suspended</span>
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Partner Branding Approval</span>
                <span class="settings-info-val">
                    Requires administrator verification before public website appearance
                </span>
            </div>
            <div class="settings-info-row align-items-start">
                <span class="settings-info-label pt-1">Vendor Item Fulfillment Steps</span>
                <div class="settings-info-val text-start" style="max-width: 65%;">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach(array_keys($vendorItemTransitions) as $state)
                            <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                {{ str_replace('_', ' ', $state) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
            <a href="{{ route('marketplace.vendors.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-people me-1"></i> Manage Vendors
            </a>
            <a href="{{ route('partners.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-handshake me-1"></i> Manage Partners
            </a>
        </div>
    </div>

</div>
@endsection

