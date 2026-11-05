@extends('front.layouts.admin')
@section('title', 'Products & Stock — Settings')
@section('page-title', 'Products & Stock')

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
        <h1 class="settings-title">Products & Stock</h1>
        <p class="settings-subtitle">Product review quality gate, critical field rules, and stock management.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    <div class="settings-form-card">
        <div class="settings-card-header">
            <h2 class="settings-card-title">Approval & Catalog Integrity Rules</h2>
            <p class="settings-card-desc">Rules safeguarding catalog quality and customer availability.</p>
        </div>

        <div class="settings-info-list mb-4">
            <div class="settings-info-row">
                <span class="settings-info-label">Vendor Product Submissions</span>
                <span class="settings-info-val">
                    Mandatory administrator review before approval and publication
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Initial Stock Segregation</span>
                <span class="settings-info-val">
                    Unapproved initial stock remains unavailable until product approval
                </span>
            </div>
            <div class="settings-info-row">
                <span class="settings-info-label">Storage Locations</span>
                <span class="settings-info-val">
                    Multi-warehouse shelf and bin tracking enabled
                </span>
            </div>
            <div class="settings-info-row align-items-start">
                <span class="settings-info-label pt-1">Critical Product Fields</span>
                <div class="settings-info-val text-start" style="max-width: 65%;">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($criticalFields as $field)
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                                {{ $field }}
                            </span>
                        @endforeach
                    </div>
                    <div class="form-text small text-muted mt-1">
                        Modifying critical fields on published products places the changes into a pending revision queue.
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 pt-2 border-top">
            <a href="{{ route('marketplace.approvals.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-check-circle me-1"></i> Product Approvals Queue
            </a>
            <a href="{{ route('marketplace.inventory.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-boxes me-1"></i> Inventory Operations
            </a>
            <a href="{{ route('admin.shop-products.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold">
                <i class="bi bi-box-seam me-1"></i> All Products
            </a>
        </div>
    </div>

</div>
@endsection

