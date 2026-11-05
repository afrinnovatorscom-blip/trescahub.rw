@extends('front.layouts.guest')
@section('content')
<main class="py-4 py-lg-5">
    <div
        class="container customer-order-tracking"
        id="account-order-tracking"
        data-tracking-url="{{ route('account.orders.tracking', $tracking['reference']) }}"
        data-refresh-token="{{ $tracking['refresh_token'] }}"
    >
        <nav class="tracking-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('account.orders') }}">My Orders</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Order {{ $tracking['reference'] }}</span>
        </nav>

        <header class="tracking-page-header">
            <div>
                <p class="tracking-eyebrow">ORDER {{ $tracking['reference'] }}</p>
                <h1>Track your order</h1>
                <p class="tracking-intro">Follow the progress of your order below.</p>
            </div>
            <div class="tracking-header-badges">
                <span class="tracking-badge is-order">{{ $tracking['status_label'] }}</span>
                <a class="tracking-receipt-button" href="{{ route('account.orders.receipt', $tracking['reference']) }}">
                    <i class="bi bi-download" aria-hidden="true"></i> Download Receipt
                </a>
            </div>
        </header>

        @include('marketplace.partials.flash')

        @if($tracking['timeline']['mode'] === 'exception')
            <div class="tracking-exception" role="status">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>{{ $tracking['timeline']['headline'] }}</span>
                @if($tracking['timeline']['exception_at'])
                    <time datetime="{{ $tracking['timeline']['exception_at']->toIso8601String() }}">
                        {{ $tracking['timeline']['exception_at']->format('M j, Y g:i A') }}
                    </time>
                @endif
            </div>
        @endif

        <section class="tracking-card tracking-progress-card" aria-labelledby="tracking-progress-title">
            <div class="tracking-section-heading">
                <div>
                    <h2 id="tracking-progress-title">Order progress</h2>
                    <p>{{ $tracking['message'] }}</p>
                </div>
                <span class="tracking-placed">
                    Placed {{ $tracking['placed_at']?->format('M j, Y') ?? '—' }}
                </span>
            </div>

            <div class="tracking-timeline-scroll" tabindex="0" role="region" aria-label="Order progress timeline">
                <ol class="tracking-timeline" data-tracking-steps>
                    @foreach($tracking['timeline']['steps'] as $step)
                        <li class="tracking-step is-{{ $step['state'] }}">
                            <span class="tracking-step-marker">
                                <i class="bi {{ $step['state'] === 'complete' ? 'bi-check-lg' : $step['icon'] }}" aria-hidden="true"></i>
                            </span>
                            <span class="tracking-step-title">{{ $step['label'] }}</span>
                            @if($step['occurred_at'])
                                <time datetime="{{ $step['occurred_at']->toIso8601String() }}">
                                    {{ $step['occurred_at']->format('M j, Y g:i A') }}
                                </time>
                            @else
                                <span class="tracking-step-pending">Pending</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
            <p class="tracking-scroll-hint">Scroll horizontally to view all order stages.</p>
        </section>

        <div class="tracking-detail-grid">
            <section class="tracking-card" aria-labelledby="order-summary-title">
                <div class="tracking-card-heading">
                    <div>
                        <h2 id="order-summary-title">Order summary</h2>
                        <p>{{ $tracking['reference'] }}</p>
                    </div>
                    <div class="tracking-summary-totals">
                        <span class="tracking-badge is-order">{{ $tracking['status_label'] }}</span>
                        <span class="tracking-total">{{ $tracking['currency'] }} {{ number_format((float) $tracking['total'], 0, '.', ',') }}</span>
                    </div>
                </div>

                <ul class="tracking-item-list" data-tracking-items>
                    @foreach($tracking['items'] as $item)
                        <li class="tracking-item">
                            @if($item['thumbnail'])
                                <img class="tracking-item-image" src="{{ $item['thumbnail'] }}" alt="" loading="lazy">
                            @else
                                <span class="tracking-item-image is-placeholder" aria-hidden="true">
                                    <i class="bi bi-box-seam"></i>
                                </span>
                            @endif
                            <div class="tracking-item-details">
                                <h3>
                                    {{ $item['name'] }}
                                    @if($item['variation_label'] && $item['variation_value'])
                                        <span>({{ $item['variation_label'] }}: {{ $item['variation_value'] }})</span>
                                    @endif
                                </h3>
                                <p>Quantity: {{ $item['quantity'] }} · {{ $item['fulfillment_label'] }} · {{ $item['assignment_label'] }}</p>
                                @if($item['vendor_name'])
                                    <p>Vendor: {{ $item['vendor_name'] }}</p>
                                @endif
                            </div>
                            <div class="tracking-item-price">
                                <span>{{ $tracking['currency'] }} {{ number_format((float) $item['unit_price'], 0, '.', ',') }} each</span>
                                <strong>{{ $tracking['currency'] }} {{ number_format((float) $item['line_total'], 0, '.', ',') }}</strong>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="tracking-order-date">
                    <span>Customer</span>
                    <strong>{{ $tracking['customer_name'] ?: '—' }}</strong>
                </div>
                <div class="tracking-order-date">
                    <span>Order date</span>
                    <strong>{{ $tracking['placed_at']?->format('M j, Y g:i A') ?? '—' }}</strong>
                </div>

                <div class="tracking-payment-records" data-tracking-payments @if(count($tracking['payments']) === 0) hidden @endif>
                    <h3>Payment records</h3>
                    <ul>
                        @foreach($tracking['payments'] as $payment)
                            <li>
                                {{ ucfirst($payment['type']) }} payment of {{ $tracking['currency'] }}
                                {{ number_format((float) $payment['amount'], 0, '.', ',') }} via
                                {{ ucfirst(str_replace('_', ' ', $payment['method'])) }}
                                @if($payment['recorded_at'])
                                    <span>{{ $payment['recorded_at']->format('M j, Y g:i A') }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="tracking-card" aria-labelledby="delivery-details-title">
                <div class="tracking-card-heading">
                    <div>
                        <h2 id="delivery-details-title">Delivery details</h2>
                        <p>Delivery information recorded for this order.</p>
                    </div>
                </div>

                <div class="tracking-deliveries" data-tracking-deliveries>
                    @forelse($tracking['deliveries'] as $delivery)
                        <article class="tracking-delivery">
                            <div class="tracking-delivery-heading">
                                <strong>{{ $delivery['reference'] }}</strong>
                                <span class="tracking-badge {{ $delivery['status'] === 'delivered' ? 'is-success' : ($delivery['status'] === 'in_transit' ? 'is-info' : 'is-neutral') }}">
                                    {{ $delivery['status_label'] }}
                                </span>
                            </div>
                            @if($delivery['delivery_method'])
                                <p><span>Method</span>{{ $delivery['delivery_method'] }}</p>
                            @endif
                            @if($delivery['courier_name'])
                                <p><span>Delivery contact</span>{{ $delivery['courier_name'] }}</p>
                            @endif
                            @if($delivery['delivery_phone'])
                                <p><span>Contact phone</span><a href="tel:{{ $delivery['delivery_phone'] }}">{{ $delivery['delivery_phone'] }}</a></p>
                            @endif
                            @if($delivery['tracking_reference'])
                                <p><span>Tracking reference</span>{{ $delivery['tracking_reference'] }}</p>
                            @endif
                            @if($delivery['message'])
                                <p class="tracking-delivery-message">{{ $delivery['message'] }}</p>
                            @endif
                            @if($delivery['delivered_at'])
                                <p><span>Delivered</span>{{ $delivery['delivered_at']->format('M j, Y g:i A') }}</p>
                            @elseif($delivery['dispatched_at'])
                                <p><span>Dispatched</span>{{ $delivery['dispatched_at']->format('M j, Y g:i A') }}</p>
                            @else
                                <p><span>Last update</span>{{ $delivery['updated_at']?->format('M j, Y g:i A') ?? 'Pending' }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="tracking-empty-delivery">A delivery record has not been created yet. Delivery details will appear here when available.</p>
                    @endforelse
                </div>

                <div class="tracking-address">
                    <span>Delivery address</span>
                    <p>{{ $tracking['delivery_address'] ?: 'No delivery address recorded.' }}</p>
                </div>

                <div class="tracking-last-update" data-last-tracking-update>
                    @if($tracking['last_tracking_update'])
                        <span>Last tracking update</span>
                        <p>{{ $tracking['last_tracking_update']['label'] }}</p>
                        <time datetime="{{ $tracking['last_tracking_update']['occurred_at']?->toIso8601String() }}">
                            {{ $tracking['last_tracking_update']['occurred_at']?->format('M j, Y g:i A') ?? '—' }}
                        </time>
                    @else
                        <span>Last tracking update</span>
                        <p>No tracking updates have been recorded yet.</p>
                    @endif
                </div>
            </section>
        </div>

        <section class="tracking-confirmation" aria-labelledby="confirm-delivery-title">
            <div>
                <h2 id="confirm-delivery-title">Received your order?</h2>
                <p>Confirm only after you have received your order.</p>
            </div>
            <div class="tracking-confirmation-actions" data-delivery-confirmation>
                @foreach($tracking['deliveries'] as $delivery)
                    @if($delivery['can_confirm'])
                        <form method="POST" action="{{ route('account.orders.delivery-confirm', [$tracking['reference'], $delivery['reference']]) }}" data-confirm-delivery>
                            @csrf
                            <button type="submit" class="tracking-confirm-button">
                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                Confirm delivery{{ count($tracking['deliveries']) > 1 ? ' '.$delivery['reference'] : '' }}
                            </button>
                        </form>
                    @endif
                @endforeach
                @if(!collect($tracking['deliveries'])->contains(fn ($delivery) => $delivery['can_confirm']))
                    <span class="tracking-confirmation-pending">Confirmation will be available when your order is out for delivery.</span>
                @endif
            </div>
        </section>

        <p class="tracking-refresh-error" data-tracking-refresh-error role="status" hidden>
            Tracking could not be refreshed. We will try again shortly.
        </p>
    </div>
</main>

<style>
    .customer-order-tracking {
        max-width: 1120px;
        color: #252a27;
    }

    .tracking-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .55rem;
        margin-bottom: 1.5rem;
        color: #6b7280;
        font-size: .875rem;
    }

    .tracking-breadcrumb a {
        color: #237a4b;
        text-decoration: none;
    }

    .tracking-breadcrumb a:hover { text-decoration: underline; }

    .tracking-page-header,
    .tracking-section-heading,
    .tracking-card-heading,
    .tracking-confirmation {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .tracking-page-header { margin-bottom: 1.5rem; }

    .tracking-eyebrow {
        margin: 0 0 .4rem;
        color: #237a4b;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .08em;
    }

    .tracking-page-header h1 {
        margin: 0;
        color: #1f2522;
        font-family: 'Space Grotesk', sans-serif;
        font-size: clamp(1.7rem, 3vw, 2.25rem);
        font-weight: 700;
    }

    .tracking-intro { margin: .45rem 0 0; color: #626b66; }

    .tracking-header-badges {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .5rem;
        padding-top: .5rem;
    }

    .tracking-badge {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dfe5e1;
        border-radius: 999px;
        padding: .35rem .7rem;
        background: #f5f7f5;
        color: #4b554f;
        font-size: .78rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .tracking-receipt-button {
        align-items: center;
        background: #237a4b;
        border: 1px solid #237a4b;
        border-radius: 8px;
        color: #fff;
        display: inline-flex;
        font-size: .82rem;
        font-weight: 600;
        gap: .45rem;
        min-height: 38px;
        padding: .5rem .8rem;
        text-decoration: none;
        white-space: nowrap;
    }

    .tracking-receipt-button:hover,
    .tracking-receipt-button:focus-visible {
        background: #1d653e;
        border-color: #1d653e;
        color: #fff;
    }

    .tracking-badge.is-order,
    .tracking-badge.is-success {
        border-color: #cce8d6;
        background: #eff8f2;
        color: #206b40;
    }

    .tracking-badge.is-warning {
        border-color: #f1dfb8;
        background: #fff9e9;
        color: #805a10;
    }

    .tracking-badge.is-danger {
        border-color: #f0caca;
        background: #fff2f2;
        color: #9c3434;
    }

    .tracking-badge.is-info {
        border-color: #d3e2f1;
        background: #f0f6fc;
        color: #365d7e;
    }

    .tracking-card {
        min-width: 0;
        border: 1px solid #e3e8e4;
        border-radius: 12px;
        padding: 1.25rem;
        background: #fff;
    }

    .tracking-progress-card { margin-bottom: 1rem; }

    .tracking-section-heading { margin-bottom: 1.25rem; }

    .tracking-section-heading h2,
    .tracking-card-heading h2,
    .tracking-confirmation h2 {
        margin: 0;
        color: #242a26;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.15rem;
        font-weight: 650;
    }

    .tracking-section-heading p,
    .tracking-card-heading p {
        margin: .3rem 0 0;
        color: #68716c;
        font-size: .9rem;
    }

    .tracking-placed {
        flex: 0 0 auto;
        color: #68716c;
        font-size: .83rem;
    }

    .tracking-timeline-scroll {
        overflow-x: auto;
        padding: .15rem 0 .5rem;
        scrollbar-color: #b7c8bd #f1f4f2;
        scrollbar-width: thin;
    }

    .tracking-timeline {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        width: 100%;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .tracking-step {
        position: relative;
        display: flex;
        min-width: 0;
        flex-direction: column;
        align-items: center;
        padding: 0 .35rem;
        text-align: center;
    }

    .tracking-step::after {
        position: absolute;
        z-index: 0;
        top: 16px;
        left: calc(50% + 17px);
        width: calc(100% - 34px);
        height: 2px;
        background: #e3e8e4;
        content: '';
    }

    .tracking-step.is-complete::after { background: #57a875; }
    .tracking-step:last-child::after { display: none; }

    .tracking-step-marker {
        position: relative;
        z-index: 1;
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border: 1px solid #dce2de;
        border-radius: 50%;
        background: #f5f7f5;
        color: #7a847e;
        font-size: 1rem;
    }

    .tracking-step.is-complete .tracking-step-marker {
        border-color: #4d9d6c;
        background: #eaf5ee;
        color: #287346;
    }

    .tracking-step.is-current .tracking-step-marker {
        border: 2px solid #378555;
        background: #f0f8f2;
        box-shadow: 0 0 0 4px #e6f3ea;
        color: #206b40;
    }

    .tracking-step-title {
        max-width: 120px;
        min-height: 2.5rem;
        margin-top: .65rem;
        color: #39413c;
        font-size: .78rem;
        font-weight: 600;
        line-height: 1.35;
    }

    .tracking-step time,
    .tracking-step-pending {
        color: #7a847e;
        font-size: .7rem;
        line-height: 1.35;
    }

    .tracking-step.is-current .tracking-step-title { color: #206b40; }
    .tracking-step-pending { color: #909993; }
    .tracking-step form { display: flex; justify-content: center; }
    .tracking-step-confirm-button {
        width: 100%;
        max-width: 120px;
        min-height: 36px;
        margin-top: .55rem;
        padding: .45rem .65rem;
        font-size: .72rem;
        line-height: 1.2;
        text-align: center;
    }
    .tracking-scroll-hint { display: none; margin: .5rem 0 0; color: #77817b; font-size: .75rem; }

    .tracking-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr);
        gap: 1rem;
    }

    .tracking-card-heading { align-items: center; margin-bottom: 1rem; }

    .tracking-total {
        color: #247748;
        font-size: 1.05rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .tracking-summary-totals { display: flex; flex-direction: column; align-items: flex-end; gap: .45rem; }

    .tracking-item-list { margin: 0; padding: 0; list-style: none; }

    .tracking-item {
        display: flex;
        align-items: center;
        gap: .85rem;
        border-top: 1px solid #edf0ed;
        padding: .9rem 0;
    }

    .tracking-item-image {
        flex: 0 0 56px;
        width: 56px;
        height: 56px;
        border-radius: 8px;
        object-fit: cover;
    }

    .tracking-item-image.is-placeholder {
        display: grid;
        place-items: center;
        background: #f1f4f2;
        color: #78827c;
        font-size: 1.25rem;
    }

    .tracking-item-details { min-width: 0; flex: 1 1 auto; }
    .tracking-item-details h3 { margin: 0; color: #29312c; font-size: .92rem; font-weight: 650; }
    .tracking-item-details h3 span { color: #657069; font-size: .83rem; font-weight: 400; }
    .tracking-item-details p { margin: .25rem 0 0; color: #6b756f; font-size: .78rem; }

    .tracking-item-price { display: flex; flex-direction: column; align-items: flex-end; gap: .2rem; text-align: right; }
    .tracking-item-price span { color: #77817b; font-size: .75rem; white-space: nowrap; }
    .tracking-item-price strong { color: #303833; font-size: .88rem; white-space: nowrap; }

    .tracking-order-date,
    .tracking-address,
    .tracking-last-update,
    .tracking-payment-records {
        border-top: 1px solid #e9edea;
        padding-top: .85rem;
        margin-top: .35rem;
    }

    .tracking-order-date { display: flex; justify-content: space-between; gap: 1rem; font-size: .83rem; }
    .tracking-order-date span,
    .tracking-address > span,
    .tracking-last-update > span { color: #707a74; }
    .tracking-order-date strong { color: #343c37; font-weight: 600; text-align: right; }

    .tracking-payment-records h3 { margin: 0 0 .45rem; color: #39413c; font-size: .82rem; font-weight: 650; }
    .tracking-payment-records ul { margin: 0; padding-left: 1rem; color: #606a64; font-size: .78rem; }
    .tracking-payment-records li + li { margin-top: .3rem; }
    .tracking-payment-records li span { display: block; color: #89918c; }

    .tracking-delivery {
        border-top: 1px solid #edf0ed;
        padding: .85rem 0;
    }

    .tracking-delivery-heading { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .5rem; }
    .tracking-delivery-heading > strong { color: #343c37; font-size: .86rem; }
    .tracking-delivery p { display: flex; flex-wrap: wrap; gap: .35rem .75rem; margin: .35rem 0; color: #4d5751; font-size: .82rem; }
    .tracking-delivery p > span { min-width: 112px; color: #78817b; }
    .tracking-delivery a { color: #237a4b; text-decoration: none; }
    .tracking-delivery-message { border-left: 2px solid #d9e8dd; padding-left: .6rem; }
    .tracking-empty-delivery { margin: 0; border-top: 1px solid #edf0ed; padding: .9rem 0; color: #737d77; font-size: .88rem; }

    .tracking-address p { margin: .4rem 0 0; color: #39413c; font-size: .88rem; }
    .tracking-last-update { margin-top: .85rem; }
    .tracking-last-update p { margin: .35rem 0 .1rem; color: #39413c; font-size: .86rem; }
    .tracking-last-update time { color: #78817b; font-size: .78rem; }

    .tracking-confirmation {
        align-items: center;
        margin-top: 1rem;
        border: 1px solid #dce8df;
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
        background: #f7faf7;
    }

    .tracking-confirmation p { margin: .3rem 0 0; color: #68716c; font-size: .85rem; }
    .tracking-confirmation-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .5rem; }

    .tracking-confirm-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        min-height: 42px;
        border: 1px solid #267849;
        border-radius: 7px;
        padding: .55rem .9rem;
        background: #267849;
        color: #fff;
        font-size: .85rem;
        font-weight: 650;
    }

    .tracking-confirm-button:hover { border-color: #1d603a; background: #1d603a; }
    .tracking-confirm-button:focus-visible { outline: 3px solid #b7ddc4; outline-offset: 2px; }
    .tracking-confirm-button:disabled { cursor: wait; opacity: .7; }
    .tracking-confirmation-pending { max-width: 290px; color: #77817b; font-size: .8rem; text-align: right; }

    .tracking-exception { display: flex; align-items: center; gap: .55rem; margin-bottom: 1rem; border: 1px solid #e9dfc5; border-radius: 8px; padding: .7rem .85rem; background: #fffaf0; color: #725a26; font-size: .87rem; }
    .tracking-exception time { margin-left: auto; color: #88784e; font-size: .77rem; }
    .tracking-refresh-error { margin: .75rem 0 0; color: #9c3434; font-size: .82rem; }

    @media (max-width: 991.98px) {
        .tracking-page-header { flex-direction: column; }
        .tracking-header-badges { justify-content: flex-start; padding-top: 0; }
        .tracking-section-heading { flex-direction: column; gap: .45rem; }
        .tracking-placed { align-self: flex-start; }
        .tracking-timeline-scroll {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            overscroll-behavior-x: contain;
            padding: .2rem .15rem .65rem;
            scroll-behavior: smooth;
            scroll-padding-inline: .5rem;
            touch-action: pan-x;
            -webkit-overflow-scrolling: touch;
        }
        .tracking-timeline-scroll:focus-visible { outline: 3px solid #b7ddc4; outline-offset: 2px; }
        .tracking-timeline-scroll::-webkit-scrollbar { height: 7px; }
        .tracking-timeline-scroll::-webkit-scrollbar-track { border-radius: 999px; background: #f1f4f2; }
        .tracking-timeline-scroll::-webkit-scrollbar-thumb { border-radius: 999px; background: #b7c8bd; }
        .tracking-timeline { width: max-content; min-width: 720px; grid-template-columns: repeat(6, minmax(120px, 1fr)); }
        .tracking-step { padding-inline: .45rem; }
        .tracking-step-title { max-width: 120px; min-height: 2.75rem; overflow-wrap: anywhere; }
        .tracking-step time,
        .tracking-step-pending { max-width: 100%; overflow-wrap: anywhere; }
        .tracking-scroll-hint { display: block; }
        .tracking-detail-grid { grid-template-columns: 1fr; }
        .tracking-confirmation { align-items: flex-start; flex-direction: column; }
        .tracking-confirmation-actions { justify-content: flex-start; }
        .tracking-confirmation-pending { max-width: none; text-align: left; }
        .tracking-exception { align-items: flex-start; }
    }

    @media (max-width: 479.98px) {
        .tracking-card { padding: 1rem; }
        .tracking-item { align-items: flex-start; gap: .6rem; }
        .tracking-item-image { flex-basis: 44px; width: 44px; height: 44px; }
        .tracking-item-price span { max-width: 82px; white-space: normal; }
        .tracking-item-price strong { font-size: .8rem; }
        .tracking-order-date { align-items: flex-start; flex-direction: column; gap: .25rem; }
        .tracking-order-date strong { text-align: left; }
        .tracking-exception { flex-wrap: wrap; }
        .tracking-exception time { width: 100%; margin-left: 1.4rem; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('account-order-tracking');
        const errorMessage = document.querySelector('[data-tracking-refresh-error]');
        if (!container || !errorMessage) return;

        document.querySelectorAll('[data-confirm-delivery]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Confirming delivery…';
                }
            });
        });

        const refreshTracking = async () => {
            if (document.visibilityState !== 'visible') return;

            try {
                const response = await fetch(container.dataset.trackingUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Tracking refresh failed with status ${response.status}.`);

                const payload = await response.json();
                if (payload.tracking.refresh_token !== container.dataset.refreshToken) {
                    window.location.reload();
                    return;
                }

                errorMessage.hidden = true;
            } catch (error) {
                console.error('Unable to refresh customer order tracking.', error);
                errorMessage.hidden = false;
            }
        };

        window.setInterval(refreshTracking, 15000);
    });
</script>
@endsection
