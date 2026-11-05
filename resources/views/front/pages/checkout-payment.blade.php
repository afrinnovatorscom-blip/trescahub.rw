@extends('front.layouts.guest')

@section('content')
@php
    $selectedPaymentMethod = $paymentMethod;
    $isAirtelSelected = $selectedPaymentMethod === 'airtel_money';
@endphp

@push('styles')
<style>
    .checkout-payment-page {
        background: #f7f9f7;
        color: #17211b;
        font-family: 'DM Sans', sans-serif;
        min-height: 65vh;
    }
    .checkout-payment-page h1,
    .checkout-payment-page h2 { font-family: 'Space Grotesk', sans-serif; }
    .checkout-payment-shell {
        background: #fff;
        border: 1px solid #e4e9e5;
        border-radius: 14px;
        box-shadow: 0 8px 26px rgba(20, 45, 28, .045);
        margin: 0 auto;
        max-width: 700px;
        padding: clamp(1.25rem, 4vw, 2.25rem);
    }
    .checkout-progress {
        align-items: flex-start;
        display: flex;
        justify-content: center;
        margin: 0 auto 2rem;
        max-width: 570px;
    }
    .checkout-progress-step {
        align-items: center;
        color: #69756c;
        display: flex;
        flex: 1 1 0;
        flex-direction: column;
        font-size: .78rem;
        gap: .45rem;
        position: relative;
        text-align: center;
    }
    .checkout-progress-step:not(:last-child)::after {
        background: #dce5dd;
        content: "";
        height: 2px;
        left: calc(50% + 17px);
        position: absolute;
        right: calc(-50% + 17px);
        top: 16px;
    }
    .checkout-progress-step.is-complete:not(:last-child)::after { background: #25964c; }
    .checkout-progress-marker {
        align-items: center;
        background: #eff3ef;
        border: 1px solid #dce5dd;
        border-radius: 50%;
        color: #69756c;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 700;
        height: 34px;
        justify-content: center;
        position: relative;
        width: 34px;
        z-index: 1;
    }
    .checkout-progress-step.is-complete .checkout-progress-marker,
    .checkout-progress-step.is-active .checkout-progress-marker {
        background: #16803c;
        border-color: #16803c;
        color: #fff;
    }
    .checkout-progress-step.is-active { color: #1d4727; font-weight: 700; }
    .checkout-payment-provider-grid {
        display: grid;
        gap: .85rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .checkout-payment-provider {
        align-items: center;
        background: #fff;
        border: 1px solid #dfe6e0;
        border-radius: 11px;
        color: inherit;
        cursor: pointer;
        display: flex;
        gap: .85rem;
        min-height: 96px;
        padding: 1rem;
        transition: border-color .16s ease, background .16s ease, box-shadow .16s ease;
    }
    .checkout-payment-provider:hover { border-color: #a9cbb1; }
    .checkout-payment-provider.is-selected {
        background: #f3faf4;
        border-color: #25964c;
        box-shadow: 0 0 0 2px rgba(37, 150, 76, .1);
    }
    .checkout-payment-provider:focus-within {
        outline: 3px solid rgba(37, 150, 76, .2);
        outline-offset: 2px;
    }
    .checkout-payment-provider input {
        accent-color: #16803c;
        flex: 0 0 auto;
        height: 17px;
        margin: 0;
        width: 17px;
    }
    .checkout-payment-provider img {
        flex: 0 0 46px;
        height: 46px;
        object-fit: contain;
        width: 46px;
    }
    .checkout-payment-provider-name { display: block; font-size: .9rem; font-weight: 700; }
    .checkout-payment-provider-description { color: #6b776e; display: block; font-size: .77rem; margin-top: .18rem; }
    .checkout-payment-page .form-label { color: #344239; font-size: .86rem; font-weight: 600; }
    .checkout-payment-page .form-control {
        border-color: #dce4dd;
        border-radius: 8px;
        min-height: 46px;
    }
    .checkout-payment-page .form-control:focus {
        border-color: #42a866;
        box-shadow: 0 0 0 .2rem rgba(22, 163, 74, .12);
    }
    .checkout-submit {
        background: #16803c;
        border: 1px solid #16803c;
        border-radius: 9px;
        color: #fff;
        font-size: .98rem;
        font-weight: 700;
        min-height: 52px;
    }
    .checkout-submit:hover,
    .checkout-submit:focus-visible { background: #116b32; border-color: #116b32; color: #fff; }
    .checkout-submit:focus-visible { box-shadow: 0 0 0 .22rem rgba(22, 128, 60, .2); }
    .checkout-submit:disabled { cursor: wait; opacity: .7; }
    @media (max-width: 575.98px) {
        .checkout-payment-page { padding-top: 1.5rem !important; }
        .checkout-payment-provider-grid { grid-template-columns: 1fr; }
        .checkout-payment-provider { min-height: 82px; }
        .checkout-progress-step { font-size: .69rem; }
    }
</style>
@endpush

<main class="checkout-payment-page py-5">
    <div class="container px-3">
        <div class="checkout-payment-shell">
            <nav class="checkout-progress" aria-label="Checkout progress">
                <div class="checkout-progress-step is-complete">
                    <span class="checkout-progress-marker" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <span>Checkout Details<br>Completed</span>
                </div>
                <div class="checkout-progress-step is-active" aria-current="step">
                    <span class="checkout-progress-marker" aria-hidden="true">2</span>
                    <span>Payment<br>Active</span>
                </div>
                <div class="checkout-progress-step">
                    <span class="checkout-progress-marker" aria-hidden="true">3</span>
                    <span>Confirmation<br>Upcoming</span>
                </div>
            </nav>

            <header class="text-center mb-4">
                <h1 class="h3 mb-2">Choose a payment method</h1>
                <p class="text-muted mb-0">Select a Mobile Money provider and enter the number to use.</p>
            </header>

            @include('marketplace.partials.flash')
            @error('checkout')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
            @error('cart')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror

            <form method="POST" action="{{ route('checkout.place') }}" id="checkout-payment-form">
                @csrf
                <fieldset class="border-0 p-0 m-0">
                    <legend class="form-label mb-3">Mobile Money Provider</legend>
                    <div class="checkout-payment-provider-grid" role="radiogroup" aria-label="Mobile money provider">
                        <label class="checkout-payment-provider {{ $selectedPaymentMethod === 'mtn_mobile_money' ? 'is-selected' : '' }}" data-provider-card>
                            <input type="radio" name="payment_method" value="mtn_mobile_money" required @checked($selectedPaymentMethod === 'mtn_mobile_money')>
                            <img src="{{ asset('assets/payment-providers/mtn-momo.png') }}" alt="MTN MoMo logo" width="46" height="46">
                            <span>
                                <span class="checkout-payment-provider-name">MTN Mobile Money</span>
                                <span class="checkout-payment-provider-description">Pay using your MTN number.</span>
                            </span>
                        </label>
                        <label class="checkout-payment-provider {{ $selectedPaymentMethod === 'airtel_money' ? 'is-selected' : '' }}" data-provider-card>
                            <input type="radio" name="payment_method" value="airtel_money" required @checked($selectedPaymentMethod === 'airtel_money')>
                            <img src="{{ asset('assets/payment-providers/airtel-money.svg') }}" alt="Airtel Money logo" width="46" height="46">
                            <span>
                                <span class="checkout-payment-provider-name">Airtel Money</span>
                                <span class="checkout-payment-provider-description">Pay using your Airtel number.</span>
                            </span>
                        </label>
                    </div>
                    @error('payment_method')<div class="invalid-feedback d-block mt-2">{{ $message }}</div>@enderror
                </fieldset>

                <div class="mt-4" id="payment-phone-panel" @if(!$selectedPaymentMethod) hidden @endif>
                    <label class="form-label" for="payment_phone" id="payment-phone-label">
                        {{ $isAirtelSelected ? 'Airtel Money Number' : 'MTN Mobile Money Number' }}
                    </label>
                    <input
                        id="payment_phone"
                        name="payment_phone"
                        type="tel"
                        class="form-control"
                        autocomplete="tel"
                        inputmode="tel"
                        placeholder="078 123 4567"
                        value="{{ $paymentPhone }}"
                        @if($selectedPaymentMethod) required @endif
                        aria-describedby="payment-phone-help @error('payment_phone') payment-phone-error @enderror"
                    >
                    <div id="payment-phone-help" class="form-text">Enter a Rwanda mobile number. It may differ from your delivery contact number.</div>
                    @error('payment_phone')<div class="invalid-feedback d-block" id="payment-phone-error">{{ $message }}</div>@enderror
                </div>

                <p class="small text-muted mt-3 mb-0">When you continue, your order is placed and a payment prompt is sent to this number. Approve it with your Mobile Money PIN to complete the payment.</p>

                <div class="d-flex flex-column-reverse flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-3 mt-4">
                    <a href="{{ route('checkout') }}" class="btn btn-outline-secondary">Back to Checkout</a>
                    <button class="btn checkout-submit px-4" type="submit" id="checkout-payment-submit">
                        <span>Place Order &amp; Pay</span>
                        <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('checkout-payment-form');
        const phonePanel = document.getElementById('payment-phone-panel');
        const phoneLabel = document.getElementById('payment-phone-label');
        const paymentPhone = document.getElementById('payment_phone');
        const submitButton = document.getElementById('checkout-payment-submit');

        const updateProvider = () => {
            const selected = form.querySelector('input[name="payment_method"]:checked');
            form.querySelectorAll('[data-provider-card]').forEach((card) => {
                card.classList.toggle('is-selected', card.querySelector('input').checked);
            });
            if (selected) {
                phonePanel.hidden = false;
                paymentPhone.required = true;
                phoneLabel.textContent = selected.value === 'airtel_money'
                    ? 'Airtel Money Number'
                    : 'MTN Mobile Money Number';
            } else {
                phonePanel.hidden = true;
                paymentPhone.required = false;
            }
        };

        form.addEventListener('change', (event) => {
            if (event.target.name === 'payment_method') updateProvider();
        });
        form.addEventListener('submit', () => {
            if (!form.reportValidity()) return;
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Sending payment prompt...';
        });
        updateProvider();
    })();
</script>
@endpush
@endsection
