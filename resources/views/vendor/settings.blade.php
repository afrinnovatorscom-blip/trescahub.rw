@extends('front.layouts.admin')
@section('page-title', 'Settings')

@push('styles')
<style>
    .vendor-settings { max-width: 900px; color: #1a2035; }
    .vendor-settings-heading h1 { margin: 0; font-size: 1.5rem; font-weight: 700; }
    .vendor-settings-heading p { margin: 5px 0 0; color: #7a8499; font-size: .9rem; }
    .vendor-settings-form { margin-top: 20px; }
    .vendor-setting-row {
        align-items: center;
        border-bottom: 1px solid #e4e9ef;
        display: flex;
        gap: 16px;
        justify-content: space-between;
        padding: 14px 2px;
    }
    .vendor-setting-copy { min-width: 0; }
    .vendor-setting-title { color: #1a2035; font-size: .92rem; font-weight: 600; }
    .vendor-setting-description { color: #7a8499; font-size: .82rem; margin-top: 3px; }
    .vendor-setting-row .form-check { flex: 0 0 auto; margin: 0; }
    .vendor-setting-row .form-check-input { cursor: pointer; margin-top: 0; }
    .vendor-setting-row .form-check-input:checked {
        background-color: #087f6b;
        border-color: #087f6b;
    }
    .vendor-setting-row .form-check-input:focus { box-shadow: 0 0 0 .2rem rgba(8, 127, 107, .18); }
    .vendor-settings-actions { display: flex; justify-content: flex-end; padding-top: 16px; }
    .vendor-settings-save {
        background: var(--accent, #00d4aa);
        border: 1px solid var(--accent, #00d4aa);
        border-radius: 8px;
        color: #0b0f1a;
        font-size: .86rem;
        font-weight: 600;
        min-height: 40px;
        padding: 8px 15px;
    }
    .vendor-settings-save:hover { background: #00c49e; border-color: #00c49e; }
    .vendor-settings-save:focus-visible { outline: 3px solid rgba(8, 127, 107, .3); outline-offset: 2px; }
    @media (max-width: 575.98px) {
        .vendor-settings-heading h1 { font-size: 1.35rem; }
        .vendor-setting-row { gap: 10px; padding: 13px 0; }
    }
</style>
@endpush

@section('content')
@php
    $preferences = $vendor->notification_preferences ?? [];
    $orderNotifications = old('preferences.order_notifications', $preferences['order_notifications'] ?? true);
    $productNotifications = old('preferences.product_notifications', $preferences['product_notifications'] ?? true);
@endphp
<main class="container-fluid py-4 vendor-settings">
    @include('marketplace.partials.flash')
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <header class="vendor-settings-heading">
        <h1>Settings</h1>
        <p>Manage your shop operating preferences.</p>
    </header>

    <form class="vendor-settings-form" method="POST" action="{{ route('vendor.settings.update') }}">
        @csrf
        @method('PUT')

        <section class="vendor-setting-row" aria-labelledby="order-notifications-title">
            <div class="vendor-setting-copy">
                <div class="vendor-setting-title" id="order-notifications-title">Order Preferences</div>
                <div class="vendor-setting-description" id="order-notifications-description">Receive notifications when an order is assigned to your shop.</div>
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="preferences[order_notifications]" value="0">
                <input class="form-check-input" id="order-notifications" type="checkbox" role="switch" name="preferences[order_notifications]" value="1" aria-labelledby="order-notifications-title" aria-describedby="order-notifications-description" @checked((bool) $orderNotifications)>
            </div>
        </section>

        <section class="vendor-setting-row" aria-labelledby="product-notifications-title">
            <div class="vendor-setting-copy">
                <div class="vendor-setting-title" id="product-notifications-title">Product Preferences</div>
                <div class="vendor-setting-description" id="product-notifications-description">Receive notifications when an administrator approves, rejects, or requests changes to your product.</div>
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="preferences[product_notifications]" value="0">
                <input class="form-check-input" id="product-notifications" type="checkbox" role="switch" name="preferences[product_notifications]" value="1" aria-labelledby="product-notifications-title" aria-describedby="product-notifications-description" @checked((bool) $productNotifications)>
            </div>
        </section>

        <div class="vendor-settings-actions">
            <button type="submit" class="vendor-settings-save">Save Changes</button>
        </div>
    </form>
</main>
@endsection
