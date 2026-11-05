@extends('front.layouts.admin')
@section('title', 'Settings')
@section('page-title', 'Settings')

@push('styles')
    @include('settings._styles')
@endpush

@section('content')
<div class="settings-page py-3">

    {{-- Page Header --}}
    <div class="settings-header">
        <h1 class="settings-title">Settings</h1>
        <p class="settings-subtitle">Manage your platform configuration.</p>
    </div>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    {{-- Group 1: MY ACCOUNT --}}
    <div class="settings-group">
        <div class="settings-group-label">MY ACCOUNT</div>
        <div class="settings-card">
            <a href="{{ route('admin.settings.profile') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Profile & Password</div>
                        <div class="settings-row-desc">Manage your administrator profile and password.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>
        </div>
    </div>

    {{-- Group 2: PLATFORM SETTINGS --}}
    <div class="settings-group">
        <div class="settings-group-label">PLATFORM SETTINGS</div>
        <div class="settings-card">
            <a href="{{ route('admin.settings.business-profile') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Business Profile</div>
                        <div class="settings-row-desc">Business name, logo, and contact information.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>

            <a href="{{ route('admin.settings.shopping-orders') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-bag-check"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Shopping & Orders</div>
                        <div class="settings-row-desc">Shop and order configuration.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>

            <a href="{{ route('admin.settings.payments-pricing') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-credit-card"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Payments & Pricing</div>
                        <div class="settings-row-desc">Payment methods, currency, and pricing configuration.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>

            <a href="{{ route('admin.settings.delivery') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Delivery</div>
                        <div class="settings-row-desc">Delivery settings and applicable fees.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>

            <a href="{{ route('admin.settings.products-stock') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Products & Stock</div>
                        <div class="settings-row-desc">Product rules, stock settings, and approval configuration.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>

            <a href="{{ route('admin.settings.vendors-partners') }}" class="settings-row">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Vendors & Partners</div>
                        <div class="settings-row-desc">Vendor and partner configuration.</div>
                    </div>
                </div>
                <i class="bi bi-chevron-right settings-row-chevron"></i>
            </a>
        </div>
    </div>

    {{-- Group 3: ADMINISTRATION --}}
    <div class="settings-group">
        <div class="settings-group-label">ADMINISTRATION</div>
        <div class="settings-card">
            <div class="settings-row" aria-disabled="true">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Team & Permissions</div>
                        <div class="settings-row-desc">Administrator accounts, roles, and permissions.</div>
                    </div>
                </div>
            </div>

            <div class="settings-row" aria-disabled="true">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-bell"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Messages & Alerts</div>
                        <div class="settings-row-desc">Notification preferences.</div>
                    </div>
                </div>
            </div>

            <div class="settings-row" aria-disabled="true">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-palette"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Website Appearance</div>
                        <div class="settings-row-desc">Existing website appearance and homepage configuration.</div>
                    </div>
                </div>
            </div>

            <div class="settings-row" aria-disabled="true">
                <div class="d-flex align-items-center gap-3 min-w-0">
                    <div class="settings-row-icon">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="settings-row-title">Security & System</div>
                        <div class="settings-row-desc">Existing security and system configuration.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
