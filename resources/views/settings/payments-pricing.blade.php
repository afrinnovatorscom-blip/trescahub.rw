@extends('front.layouts.admin')
@section('title', 'Payments & Pricing — Settings')
@section('page-title', 'Payments & Pricing')

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
        <h1 class="settings-title">Payments & Pricing</h1>
        <p class="settings-subtitle">Payment methods, base currency, and customer checkout instructions.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Currency & Checkout Methods</h2>
            <p class="settings-card-desc">Supported currencies and active storefront payment channels.</p>
        </div>

        <div class="settings-info-list mb-4">
            <div class="settings-info-row">
                <span class="settings-info-label">Base Marketplace Currency</span>
                <span class="settings-info-val">
                    <span class="badge bg-light text-dark border font-monospace fs-6">{{ $currency }}</span>
                    <span class="text-muted small ms-1">(Rwandan Franc)</span>
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Active Storefront Payment Channels</span>
                <span class="settings-info-val">
                    @foreach($paymentMethods as $key => $name)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">
                            {{ $name }}
                        </span>
                    @endforeach
                </span>
            </div>
            <div class="settings-info-row align-items-start">
                <span class="settings-info-label pt-1">Bank & Offline Instructions</span>
                <div class="settings-info-val text-start" style="max-width: 60%;">
                    <div class="p-2 bg-light rounded border text-muted small">
                        {{ $bankInstructions }}
                    </div>
                </div>
            </div>
        </div>

        <div class="p-3 bg-light rounded-3 border">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-shield-check text-success"></i>
                <span class="fw-semibold small text-dark">Financial Access Control</span>
            </div>
            <p class="text-muted small mb-0">
                Product acquisition costs, financial margins, and revenue reports require the dedicated <code class="text-dark">finance.view</code> permission assigned to the administrator account.
            </p>
        </div>
    </div>

</div>
@endsection

