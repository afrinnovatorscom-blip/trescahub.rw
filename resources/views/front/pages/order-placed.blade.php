@extends('front.layouts.guest')
@section('content')
@push('styles')
<style>
    .order-confirmation-page {
        background: #f7f9f7;
        color: #17211b;
        font-family: 'DM Sans', sans-serif;
        min-height: 65vh;
    }
    .order-confirmation-page h1 { font-family: 'Space Grotesk', sans-serif; }
    .order-confirmation-shell {
        background: #fff;
        border: 1px solid #e4e9e5;
        border-radius: 14px;
        box-shadow: 0 8px 26px rgba(20, 45, 28, .045);
        margin: 0 auto;
        max-width: 760px;
        padding: clamp(1.25rem, 4vw, 2.25rem);
    }
    .order-confirmation-progress {
        align-items: flex-start;
        display: flex;
        justify-content: center;
        margin: 0 auto 2rem;
        max-width: 570px;
    }
    .order-confirmation-progress-step {
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
    .order-confirmation-progress-step:not(:last-child)::after {
        background: #25964c;
        content: "";
        height: 2px;
        left: calc(50% + 17px);
        position: absolute;
        right: calc(-50% + 17px);
        top: 16px;
    }
    .order-confirmation-progress-marker {
        align-items: center;
        background: #16803c;
        border: 1px solid #16803c;
        border-radius: 50%;
        color: #fff;
        display: inline-flex;
        font-size: .78rem;
        font-weight: 700;
        height: 34px;
        justify-content: center;
        position: relative;
        width: 34px;
        z-index: 1;
    }
    .order-confirmation-icon {
        align-items: center;
        background: #eaf6ed;
        border-radius: 50%;
        color: #16803c;
        display: inline-flex;
        font-size: 2rem;
        height: 72px;
        justify-content: center;
        margin-bottom: 1rem;
        width: 72px;
    }
    .order-confirmation-reference {
        background: #f7faf7;
        border: 1px solid #e4e9e5;
        border-radius: 9px;
        color: #344239;
        padding: .75rem 1rem;
    }
    @media (max-width: 575.98px) {
        .order-confirmation-page { padding-top: 1.5rem !important; }
        .order-confirmation-progress-step { font-size: .69rem; }
    }
</style>
@endpush

<main class="order-confirmation-page py-5">
    <div class="container px-3">
        <div class="order-confirmation-shell">
            <nav class="order-confirmation-progress" aria-label="Checkout progress">
                <div class="order-confirmation-progress-step">
                    <span class="order-confirmation-progress-marker" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <span>Checkout Details<br>Completed</span>
                </div>
                <div class="order-confirmation-progress-step">
                    <span class="order-confirmation-progress-marker" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <span>Payment Details<br>Submitted</span>
                </div>
                <div class="order-confirmation-progress-step" aria-current="step" style="color:#1d4727;font-weight:700;">
                    <span class="order-confirmation-progress-marker" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                    <span>Confirmation<br>Complete</span>
                </div>
            </nav>

            <div class="text-center">
                <span class="order-confirmation-icon" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
                <h1 class="h3 mb-2">Order Received Successfully</h1>
                <p class="text-muted mb-4">Thank you for your order. We have received your order and it will be processed soon.</p>

                <div class="order-confirmation-reference text-start mb-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <span>Order reference</span>
                        <strong>{{ $tracking['reference'] }}</strong>
                    </div>
                    @foreach($tracking['items'] as $item)
                        <div class="d-flex justify-content-between gap-3 border-top py-2">
                            <span>{{ $item['name'] }}@if($item['variation_label'] && $item['variation_value'])<br><small class="text-muted">{{ $item['variation_label'] }}: {{ $item['variation_value'] }}</small>@endif</span>
                            <span class="text-nowrap">× {{ $item['quantity'] }}</span>
                        </div>
                    @endforeach
                    <div class="d-flex justify-content-between gap-3 border-top pt-3 mt-1">
                        <strong>Order total</strong>
                        <strong>{{ $tracking['currency'] }} {{ number_format((float) $tracking['total'], 0, '.', ',') }}</strong>
                    </div>
                </div>

                <a class="btn btn-success px-4 py-2" href="{{ $trackingUrl }}">
                    <i class="bi bi-box-seam me-2"></i>Track your order
                </a>
            </div>

            @include('marketplace.partials.flash')
        </div>
    </div>
</main>
@endsection
