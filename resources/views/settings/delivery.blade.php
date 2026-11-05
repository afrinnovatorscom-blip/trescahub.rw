@extends('front.layouts.admin')
@section('title', 'Delivery — Settings')
@section('page-title', 'Delivery')

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
        <h1 class="settings-title">Delivery</h1>
        <p class="settings-subtitle">Fulfillment methods, dispatch requirements, and delivery settings.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Delivery Methods & Fulfillment Rules</h2>
            <p class="settings-card-desc">Supported logistics channels and proof of delivery verification.</p>
        </div>

        <div class="settings-info-list mb-4">
            <div class="settings-info-row">
                <span class="settings-info-label">Supported Delivery Methods</span>
                <span class="settings-info-val">
                    @foreach($deliveryMethods as $key => $label)
                        <span class="badge bg-light text-dark border me-1">
                            <i class="bi bi-box me-1 text-primary"></i> {{ $label }}
                        </span>
                    @endforeach
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Applicable Delivery Surcharge</span>
                <span class="settings-info-val text-success">
                    Standard / Included in order total
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Dispatch Verification</span>
                <span class="settings-info-val">
                    Courier details and tracking reference required before dispatch
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Proof of Delivery</span>
                <span class="settings-info-val">
                    Signed proof document upload supported on completion
                </span>
            </div>
        </div>

        <div class="pt-2 border-top">
            <a href="{{ route('marketplace.deliveries.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-truck me-1"></i> Open Deliveries Management
            </a>
        </div>
    </div>

</div>
@endsection

