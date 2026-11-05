@extends('front.layouts.guest')

@section('content')
@php
    $subtotal = (float) $lines->sum('line');
    $deliveryAddress = old('delivery_address', $checkoutDetails['delivery_address'] ?? $defaultAddress?->formattedAddress() ?? '');
    $hasCustomAddress = old('delivery_address') !== null
        || (isset($checkoutDetails['delivery_address'])
            && $checkoutDetails['delivery_address'] !== $defaultAddress?->formattedAddress());
    $addressIsSaved = $defaultAddress && ! $hasCustomAddress;
@endphp

@push('styles')
<style>
    .checkout-page {
        background: #f7f9f7;
        color: #17211b;
        font-family: 'DM Sans', sans-serif;
        min-height: 65vh;
    }
    .checkout-page h1,
    .checkout-page h2,
    .checkout-page h3 {
        font-family: 'Space Grotesk', sans-serif;
    }
    .checkout-header { margin-bottom: 1.75rem; }
    .checkout-eyebrow {
        color: #16803c;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    .checkout-section,
    .checkout-summary {
        background: #fff;
        border: 1px solid #e4e9e5;
        border-radius: 14px;
        box-shadow: 0 8px 26px rgba(20, 45, 28, .045);
    }
    .checkout-section { padding: clamp(1.1rem, 3vw, 1.6rem); }
    .checkout-section + .checkout-section { margin-top: 1rem; }
    .checkout-section-title {
        align-items: flex-start;
        display: flex;
        gap: .75rem;
        margin-bottom: 1.2rem;
    }
    .checkout-section-step {
        align-items: center;
        background: #edf7ef;
        border-radius: 50%;
        color: #16803c;
        display: inline-flex;
        flex: 0 0 30px;
        font-size: .8rem;
        font-weight: 700;
        height: 30px;
        justify-content: center;
        width: 30px;
    }
    .checkout-page .form-label { color: #344239; font-size: .86rem; font-weight: 600; }
    .checkout-page .form-control {
        border-color: #dce4dd;
        border-radius: 8px;
        min-height: 46px;
    }
    .checkout-page textarea.form-control { min-height: 96px; }
    .checkout-page .form-control:focus {
        border-color: #42a866;
        box-shadow: 0 0 0 .2rem rgba(22, 163, 74, .12);
    }
    .checkout-saved-address {
        background: #f8faf8;
        border: 1px solid #e5ebe6;
        border-radius: 9px;
        color: #4b5a4f;
        font-size: .88rem;
        padding: .8rem .9rem;
    }
    .checkout-change-address {
        background: none;
        border: 0;
        color: #16803c;
        font-size: .84rem;
        font-weight: 700;
        padding: 0;
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .checkout-summary {
        overflow: hidden;
        position: sticky;
        top: 88px;
    }
    .checkout-summary-header { border-bottom: 1px solid #e9eee9; padding: 1.25rem 1.35rem; }
    .checkout-summary-items {
        display: grid;
        gap: 1rem;
        max-height: 330px;
        overflow-y: auto;
        padding: 1.25rem 1.35rem;
    }
    .checkout-summary-item { align-items: center; display: flex; gap: .85rem; min-width: 0; }
    .checkout-product-image {
        align-items: center;
        background: #f3f6f3;
        border: 1px solid #e8ede8;
        border-radius: 9px;
        color: #829087;
        display: flex;
        flex: 0 0 58px;
        height: 58px;
        justify-content: center;
        overflow: hidden;
        width: 58px;
    }
    .checkout-product-image img { height: 100%; object-fit: cover; width: 100%; }
    .checkout-item-details { min-width: 0; }
    .checkout-item-name {
        color: #26342a;
        display: -webkit-box;
        font-size: .87rem;
        font-weight: 600;
        line-clamp: 2;
        overflow: hidden;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }
    .checkout-item-meta { color: #758078; font-size: .77rem; margin-top: .2rem; }
    .checkout-item-price { color: #27382b; font-size: .85rem; font-weight: 700; margin-left: auto; white-space: nowrap; }
    .checkout-summary-totals { border-top: 1px solid #e9eee9; padding: 1rem 1.35rem 1.35rem; }
    .checkout-total-row { align-items: center; color: #647168; display: flex; font-size: .88rem; justify-content: space-between; padding: .35rem 0; }
    .checkout-grand-total {
        align-items: center;
        background: #eff8f0;
        border: 1px solid #d9eedc;
        border-radius: 9px;
        color: #1d4727;
        display: flex;
        font-size: 1rem;
        font-weight: 700;
        justify-content: space-between;
        margin-top: .8rem;
        padding: .9rem 1rem;
    }
    .checkout-payment-note { color: #69756c; font-size: .8rem; }
    .checkout-submit {
        background: #16803c;
        border: 1px solid #16803c;
        border-radius: 9px;
        color: #fff;
        font-size: .98rem;
        font-weight: 700;
        min-height: 52px;
        transition: background .16s ease, box-shadow .16s ease;
    }
    .checkout-submit:hover,
    .checkout-submit:focus-visible { background: #116b32; border-color: #116b32; color: #fff; }
    .checkout-submit:focus-visible { box-shadow: 0 0 0 .22rem rgba(22, 128, 60, .2); }
    .checkout-submit:disabled { cursor: wait; opacity: .7; }
    .checkout-submit i { font-size: 1rem; }
    @media (min-width: 992px) {
        .checkout-layout {
            display: grid;
            grid-template-areas:
                "details summary"
                "action summary";
            grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
            column-gap: 1.5rem;
            row-gap: 1rem;
            margin: 0;
        }
        .checkout-layout > .checkout-details,
        .checkout-layout > .checkout-summary-column,
        .checkout-layout > .checkout-submit-column {
            width: auto;
            max-width: none;
            padding: 0;
        }
        .checkout-details { grid-area: details; }
        .checkout-summary-column { grid-area: summary; }
        .checkout-submit-column { grid-area: action; }
    }
    @media (max-width: 991.98px) {
        .checkout-summary { position: static; }
        .checkout-summary-items { max-height: none; }
    }
    @media (max-width: 575.98px) {
        .checkout-page { padding-top: 1.5rem !important; }
        .checkout-header { margin-bottom: 1.25rem; }
    }
</style>
@endpush

<main class="checkout-page py-5">
    <div class="container" style="max-width: 1120px;">
        <header class="checkout-header">
            <div class="checkout-eyebrow mb-2">Your order</div>
            <h1 class="h2 mb-1">Checkout</h1>
            <p class="mb-0 text-muted">Confirm your contact and delivery details.</p>
        </header>

        @include('marketplace.partials.flash')

        <form method="POST" action="{{ route('checkout.details') }}" id="checkout-form">
            @csrf
            <div class="row g-4 align-items-start checkout-layout">
                <div class="col-12 col-lg-7 checkout-details">
                    <section class="checkout-section" aria-labelledby="checkout-customer-title">
                        <div class="checkout-section-title">
                            <span class="checkout-section-step" aria-hidden="true">1</span>
                            <div>
                                <h2 class="h5 mb-1" id="checkout-customer-title">Customer Information</h2>
                                <p class="small text-muted mb-0">Who should we contact about this order?</p>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="customer_name">Full Name</label>
                                <input id="customer_name" name="customer_name" class="form-control" autocomplete="name" required value="{{ old('customer_name', $checkoutDetails['customer_name'] ?? $defaultAddress?->recipient_name ?? $customer?->name ?? '') }}">
                                @error('customer_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label" for="phone">Phone Number</label>
                                <input id="phone" name="phone" type="tel" class="form-control" autocomplete="tel" required value="{{ old('phone', $checkoutDetails['phone'] ?? $defaultAddress?->recipient_phone ?? $customer?->phone ?? '') }}">
                                @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Email Address <span class="fw-normal text-muted">(optional)</span></label>
                                <input id="email" type="email" name="email" class="form-control" autocomplete="email" value="{{ old('email', $checkoutDetails['email'] ?? $defaultAddress?->recipient_email ?? $customer?->email ?? '') }}">
                                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-address-title">
                        <div class="checkout-section-title">
                            <span class="checkout-section-step" aria-hidden="true">2</span>
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 w-100">
                                <div>
                                    <h2 class="h5 mb-1" id="checkout-address-title">Order / Delivery Address</h2>
                                    <p class="small text-muted mb-0">Where should your order be delivered?</p>
                                </div>
                                @if($defaultAddress)
                                    <button type="button" class="checkout-change-address" data-address-toggle aria-controls="delivery_address" aria-pressed="{{ $addressIsSaved ? 'false' : 'true' }}">{{ $addressIsSaved ? 'Change Address' : 'Use saved address' }}</button>
                                @endif
                            </div>
                        </div>
                        @if($defaultAddress)
                            <div class="checkout-saved-address mb-3">
                                <span class="d-block fw-semibold text-dark mb-1">Saved address · {{ $defaultAddress->label }}</span>
                                <span>{{ $defaultAddress->formattedAddress() }}</span>
                            </div>
                        @else
                            <div class="checkout-saved-address mb-3">No saved delivery address. Enter your delivery address below.</div>
                        @endif
                        <label class="form-label" for="delivery_address">Delivery address</label>
                        <textarea id="delivery_address" name="delivery_address" class="form-control" autocomplete="street-address" required @if($addressIsSaved) readonly data-saved-address @endif>{{ $deliveryAddress }}</textarea>
                        @error('delivery_address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if($customer)
                            <a href="{{ route('account.profile') }}" class="d-inline-block small mt-2 text-decoration-none">Manage saved addresses</a>
                        @endif
                        <div class="mt-3">
                            <label class="form-label" for="delivery_notes">Delivery notes <span class="fw-normal text-muted">(optional)</span></label>
                            <textarea id="delivery_notes" name="delivery_notes" class="form-control" placeholder="Landmark, preferred delivery time, or other instructions">{{ old('delivery_notes', $checkoutDetails['delivery_notes'] ?? $defaultAddress?->delivery_instructions) }}</textarea>
                            @error('delivery_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </section>

                </div>

                <aside class="col-12 col-lg-5 checkout-summary-column" aria-labelledby="checkout-summary-title">
                    <section class="checkout-summary">
                        <header class="checkout-summary-header">
                            <h2 class="h5 mb-0" id="checkout-summary-title">Order Summary</h2>
                        </header>
                        <div class="checkout-summary-items">
                            @foreach($lines as $line)
                                @php
                                    $product = $line['product'];
                                    $imagePath = $product->thumbnail
                                        ? asset('assets/' . ltrim(str_replace('public/assets/', '', $product->thumbnail), '/'))
                                        : null;
                                @endphp
                                <article class="checkout-summary-item">
                                    <div class="checkout-product-image" aria-hidden="{{ $imagePath ? 'true' : 'false' }}">
                                        @if($imagePath)
                                            <img src="{{ $imagePath }}" alt="" loading="lazy">
                                        @else
                                            <i class="bi bi-box-seam" aria-hidden="true"></i>
                                        @endif
                                    </div>
                                    <div class="checkout-item-details">
                                        <span class="checkout-item-name">{{ $product->product_name }}</span>
                                        @if($line['variation'])
                                            <span class="checkout-item-meta d-block">{{ $line['variation']->feature_label }}: {{ $line['variation']->variation_value }}</span>
                                        @endif
                                        <span class="checkout-item-meta d-block">Qty {{ $line['quantity'] }} · RWF {{ number_format($line['unit_price'], 0, '.', ',') }} each</span>
                                    </div>
                                    <span class="checkout-item-price">RWF {{ number_format($line['line'], 0, '.', ',') }}</span>
                                </article>
                            @endforeach
                        </div>
                        <div class="checkout-summary-totals">
                            <div class="checkout-total-row">
                                <span>Subtotal</span>
                                <strong>RWF {{ number_format($subtotal, 0, '.', ',') }}</strong>
                            </div>
                            <div class="checkout-total-row">
                                <span>Delivery fee</span>
                                <span class="text-end">Not charged at checkout</span>
                            </div>
                            <div class="checkout-grand-total">
                                <span>Total Amount</span>
                                <span>RWF {{ number_format($subtotal, 0, '.', ',') }}</span>
                            </div>
                            <p class="checkout-payment-note mt-3 mb-0">The total uses current cart prices. No delivery fee is configured for checkout.</p>
                        </div>
                    </section>
                </aside>
                <div class="col-12 col-lg-7 checkout-submit-column">
                    <button class="btn checkout-submit w-100" type="submit" id="checkout-submit">
                        <span>Continue to Payment</span>
                        <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('checkout-form');
        const submitButton = document.getElementById('checkout-submit');

        form.addEventListener('submit', () => {
            if (!form.reportValidity()) return;
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Continuing...';
        });

        const addressToggle = document.querySelector('[data-address-toggle]');
        if (addressToggle) {
            const addressField = document.getElementById('delivery_address');
            addressToggle.addEventListener('click', () => {
                const isReadOnly = addressField.hasAttribute('readonly');
                addressField.toggleAttribute('readonly', !isReadOnly);
                addressToggle.textContent = isReadOnly ? 'Use saved address' : 'Change Address';
                addressToggle.setAttribute('aria-pressed', isReadOnly ? 'true' : 'false');
                if (isReadOnly) addressField.focus();
            });
        }
    })();
</script>
@endpush
@endsection
