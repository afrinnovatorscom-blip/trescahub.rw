@extends('front.layouts.guest')

@section('content')
@php
    $state = match (true) {
        $order->payment_status === 'paid' => 'completed',
        $transaction === null => 'none',
        default => $transaction->status,
    };
    $isAirtelSelected = $paymentMethod === 'airtel_money';
    $statusUrl = $transaction ? route('checkout.payment.status', $transaction) : null;
@endphp

@push('styles')
<style>
    .order-payment-page {
        background: #f7f9f7;
        color: #17211b;
        font-family: 'DM Sans', sans-serif;
        min-height: 65vh;
    }
    .order-payment-page h1 { font-family: 'Space Grotesk', sans-serif; }
    .order-payment-shell {
        background: #fff;
        border: 1px solid #e4e9e5;
        border-radius: 14px;
        box-shadow: 0 8px 26px rgba(20, 45, 28, .045);
        margin: 0 auto;
        max-width: 640px;
        padding: clamp(1.25rem, 4vw, 2.25rem);
    }
    .order-payment-icon {
        align-items: center;
        border-radius: 50%;
        display: flex;
        font-size: 1.9rem;
        height: 68px;
        justify-content: center;
        margin: 0 auto 1.1rem;
        width: 68px;
    }
    .order-payment-icon.is-pending { background: #fff4e5; color: #c46a00; }
    .order-payment-icon.is-completed { background: #e7f6ec; color: #16803c; }
    .order-payment-icon.is-failed { background: #fdecec; color: #c0392b; }
    .order-payment-icon.is-none { background: #eff3ef; color: #3c5a44; }
    .order-payment-pulse { animation: order-payment-pulse 1.6s ease-in-out infinite; }
    @keyframes order-payment-pulse { 50% { opacity: .45; } }
    @media (prefers-reduced-motion: reduce) { .order-payment-pulse { animation: none; } }
    .order-payment-summary {
        border-top: 1px solid #e9eee9;
        font-size: .88rem;
        margin: 1.75rem 0 0;
    }
    .order-payment-summary div {
        border-bottom: 1px solid #f0f3f0;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: .6rem 0;
    }
    .order-payment-summary dt { color: #69756c; font-weight: 500; }
    .order-payment-summary dd { font-weight: 700; margin: 0; text-align: right; word-break: break-all; }
    .order-payment-provider-grid { display: grid; gap: .75rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .order-payment-provider {
        align-items: center;
        border: 1px solid #dfe6e0;
        border-radius: 11px;
        cursor: pointer;
        display: flex;
        gap: .7rem;
        padding: .8rem;
    }
    .order-payment-provider:has(input:checked) { background: #f3faf4; border-color: #25964c; }
    .order-payment-provider input { accent-color: #16803c; }
    .order-payment-provider img { height: 36px; object-fit: contain; width: 36px; }
    .order-payment-page .form-label { color: #344239; font-size: .86rem; font-weight: 600; }
    .order-payment-page .form-control { border-color: #dce4dd; border-radius: 8px; min-height: 46px; }
    .order-payment-btn {
        background: #16803c;
        border: 1px solid #16803c;
        border-radius: 9px;
        color: #fff;
        font-weight: 700;
        min-height: 48px;
    }
    .order-payment-btn:hover, .order-payment-btn:focus-visible { background: #116b32; color: #fff; }
    .order-payment-btn:disabled { cursor: wait; opacity: .7; }
    @media (max-width: 575.98px) {
        .order-payment-page { padding-top: 1.5rem !important; }
        .order-payment-provider-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

<main class="order-payment-page py-5">
    <div class="container px-3">
        <div class="order-payment-shell text-center" id="order-payment" data-state="{{ $state }}">
            @include('marketplace.partials.flash')
            @if($errors->any())
                <div class="alert alert-danger text-start" role="alert">{{ $errors->first() }}</div>
            @endif

            <section data-state-panel="pending" @if($state !== 'pending') hidden @endif aria-live="polite">
                <div class="order-payment-icon is-pending"><i class="bi bi-phone order-payment-pulse" aria-hidden="true"></i></div>
                <h1 class="h3 mb-2">Approve the payment on your phone</h1>
                <p class="text-muted mb-1">
                    We sent a Mobile Money prompt to <strong>{{ $transaction?->phone }}</strong>.
                    Enter your PIN to pay <strong>RWF {{ number_format((float) $transaction?->amount) }}</strong>.
                </p>
                <p class="small text-muted mb-0">
                    No prompt? MTN users can dial <strong>*182*7*1#</strong> to approve pending payments.
                </p>
                <p class="small text-muted mt-3 mb-0">
                    <span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                    Waiting for confirmation…
                </p>
            </section>

            <section data-state-panel="completed" @if($state !== 'completed') hidden @endif>
                <div class="order-payment-icon is-completed"><i class="bi bi-check-lg" aria-hidden="true"></i></div>
                <h1 class="h3 mb-2">Payment successful</h1>
                <p class="text-muted mb-4">Thank you! We received your payment for order <strong>{{ $order->reference }}</strong>.</p>
                <a href="{{ route('checkout.done', $order->reference) }}" class="btn order-payment-btn px-4">
                    View order confirmation <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
                </a>
            </section>

            <section data-state-panel="failed" @if(! in_array($state, ['failed', 'none'], true)) hidden @endif>
                @if($state === 'none')
                    <div class="order-payment-icon is-none"><i class="bi bi-wallet2" aria-hidden="true"></i></div>
                    <h1 class="h3 mb-2">Pay for your order</h1>
                    <p class="text-muted mb-0">Choose a Mobile Money provider and the number to charge.</p>
                @else
                    <div class="order-payment-icon is-failed"><i class="bi bi-x-lg" aria-hidden="true"></i></div>
                    <h1 class="h3 mb-2">Payment not completed</h1>
                    <p class="text-muted mb-0" data-failure-reason>
                        {{ $transaction?->failure_reason ?: 'The payment was declined, cancelled or timed out.' }}
                    </p>
                    <p class="small text-muted mt-1 mb-0">Your order is saved. You can try again below.</p>
                @endif

                @if($canPay)
                    <form method="POST" action="{{ route('account.orders.pay.submit', $order->reference) }}" class="text-start mt-4" id="order-payment-form">
                        @csrf
                        <fieldset class="border-0 p-0 m-0">
                            <legend class="form-label mb-2">Mobile Money Provider</legend>
                            <div class="order-payment-provider-grid">
                                <label class="order-payment-provider">
                                    <input type="radio" name="payment_method" value="mtn_mobile_money" required @checked($paymentMethod === 'mtn_mobile_money')>
                                    <img src="{{ asset('assets/payment-providers/mtn-momo.png') }}" alt="" width="36" height="36">
                                    <span class="fw-semibold small">MTN Mobile Money</span>
                                </label>
                                <label class="order-payment-provider">
                                    <input type="radio" name="payment_method" value="airtel_money" required @checked($isAirtelSelected)>
                                    <img src="{{ asset('assets/payment-providers/airtel-money.svg') }}" alt="" width="36" height="36">
                                    <span class="fw-semibold small">Airtel Money</span>
                                </label>
                            </div>
                        </fieldset>
                        <label class="form-label mt-3" for="payment_phone">Mobile Money Number</label>
                        <input id="payment_phone" name="payment_phone" type="tel" class="form-control" inputmode="tel"
                               autocomplete="tel" placeholder="078 123 4567" value="{{ $paymentPhone }}" required>
                        <button class="btn order-payment-btn w-100 mt-3" type="submit">
                            <span>Pay RWF {{ number_format($amountDue) }}</span>
                        </button>
                    </form>
                @endif
            </section>

            <dl class="order-payment-summary text-start">
                <div><dt>Order</dt><dd>{{ $order->reference }}</dd></div>
                <div><dt>Order total</dt><dd>RWF {{ number_format((float) $order->total) }}</dd></div>
                @if($transaction)
                    <div><dt>Payment reference</dt><dd class="small">{{ $transaction->request_transaction_id }}</dd></div>
                @endif
            </dl>

            <a href="{{ route('account.orders.show', $order->reference) }}" class="btn btn-link mt-3">View my order</a>
        </div>
    </div>
</main>

@push('scripts')
<script>
    (() => {
        const form = document.getElementById('order-payment-form');
        if (form) {
            form.addEventListener('submit', () => {
                if (!form.reportValidity()) return;
                const button = form.querySelector('button[type="submit"]');
                button.disabled = true;
                button.querySelector('span').textContent = 'Sending payment prompt...';
            });
        }

        const root = document.getElementById('order-payment');
        const statusUrl = @json($statusUrl);
        if (!statusUrl || root.dataset.state !== 'pending') return;

        const show = (state, message) => {
            root.dataset.state = state;
            root.querySelectorAll('[data-state-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.statePanel !== state;
            });
            if (state === 'failed') {
                // Reload so the retry form is rendered with fresh server state
                window.location.reload();
            }
        };

        let attempts = 0;
        const poll = () => {
            if (++attempts > 60) return; // stop after ~5 minutes; the webhook still updates the order
            fetch(statusUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => response.json())
                .then((data) => {
                    if (data.status && data.status !== 'pending') {
                        show(data.status, data.message);
                        return;
                    }
                    setTimeout(poll, 5000);
                })
                .catch(() => setTimeout(poll, 5000));
        };
        setTimeout(poll, 5000);
    })();
</script>
@endpush
@endsection
