@extends('front.layouts.guest')
@section('content')
<main class="py-5">
    <div class="container account-orders">
        <h1 class="h3 mb-3">My Orders</h1>
        @include('marketplace.partials.flash')

        <div class="account-order-list">
            @forelse($orders as $order)
                @php
                    $inTransitDeliveries = $order->deliveries->where('status', 'in_transit');
                    $orderStatusClass = match ($order->fulfillment_status) {
                        'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'delivered', 'completed' => 'is-positive',
                        'pending_confirmation', 'cancellation_requested' => 'is-pending',
                        'cancelled', 'rejected', 'returned' => 'is-negative',
                        default => 'is-neutral',
                    };
                @endphp
                <article class="account-order-row">
                    <div class="account-order-main">
                        <div class="account-order-heading">
                            <strong class="account-order-reference">{{ $order->reference }}</strong>
                            <span class="account-order-date">{{ $order->created_at?->format('M j, Y') }}</span>
                        </div>
                        <div class="account-order-status-mobile">
                            <span class="account-order-status {{ $orderStatusClass }}">{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</span>
                        </div>

                        <ul class="account-order-items">
                            @foreach($order->items as $item)
                                <li>
                                    <span class="account-order-product">{{ $item->product_name }}</span>
                                    @if($item->variation_label && $item->variation_value)
                                        <span class="account-order-variation">({{ $item->variation_label }}: {{ $item->variation_value }})</span>
                                    @endif
                                    <span class="account-order-quantity">× {{ $item->quantity }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="account-order-contact-mobile">
                            @if($order->phone)
                                <span><i class="bi bi-telephone" aria-hidden="true"></i>{{ $order->phone }}</span>
                            @endif
                            @if($order->delivery_address)
                                <span><i class="bi bi-geo-alt" aria-hidden="true"></i>{{ $order->delivery_address }}</span>
                            @endif
                        </div>

                        <div class="account-order-meta">
                            <span>Delivery</span>
                            @forelse($order->deliveries->unique('status') as $delivery)
                                @php
                                    $deliveryBadge = match ($delivery->status) {
                                        'in_transit' => 'is-info',
                                        'delivered' => 'is-success',
                                        'failed' => 'is-danger',
                                        default => 'is-neutral',
                                    };
                                @endphp
                                <span class="account-order-badge {{ $deliveryBadge }}">{{ \App\Support\CustomerTracking::deliveryLabel($delivery->status) }}</span>
                            @empty
                                <span class="account-order-badge is-neutral">Not created</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="account-order-side">
                        <div class="account-order-summary">
                            <div class="account-order-total">RWF {{ number_format((float) $order->total, 0, '.', ',') }}</div>
                            <a class="account-order-details" href="{{ route('account.orders.show', $order->reference) }}">
                                Track order <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="dropdown account-order-menu-column">
                            <button
                                class="account-order-menu-button dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-display="static"
                                aria-expanded="false"
                                aria-label="More actions for order {{ $order->reference }}"
                                title="More order actions"
                            >
                                <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end account-order-menu">
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.orders.show', $order->reference) }}">
                                        <i class="bi bi-receipt" aria-hidden="true"></i> View details
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('account.orders.receipt', $order->reference) }}">
                                        <i class="bi bi-download" aria-hidden="true"></i> Download Receipt
                                    </a>
                                </li>
                                @foreach($inTransitDeliveries as $delivery)
                                    <li>
                                        <form
                                            method="POST"
                                            action="{{ route('account.orders.delivery-confirm', [$order->reference, $delivery->reference]) }}"
                                            onsubmit="return confirm('Confirm that delivery {{ $delivery->reference }} has arrived?')"
                                        >
                                            @csrf
                                            <button class="dropdown-item" type="submit">
                                                <i class="bi bi-check-circle" aria-hidden="true"></i>
                                                Mark{{ $inTransitDeliveries->count() > 1 ? ' '.$delivery->reference : '' }} as delivered
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </article>
            @empty
                <p class="text-muted mb-0">You have no orders yet.</p>
            @endforelse
        </div>

        <div class="mt-3">{{ $orders->links() }}</div>
    </div>
</main>

<style>
    .account-order-list {
        border-top: 1px solid #e5e7eb;
    }

    .account-order-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        padding: 1.1rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .account-order-main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .account-order-heading {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: .35rem .8rem;
    }

    .account-order-reference {
        color: #111827;
        font-family: 'Space Grotesk', sans-serif;
        font-size: .98rem;
        letter-spacing: -.01em;
    }

    .account-order-date,
    .account-order-variation {
        color: #6b7280;
        font-size: .8rem;
    }

    .account-order-items {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem 1rem;
        margin: .45rem 0 .6rem;
        padding: 0;
        list-style: none;
        color: #374151;
        font-size: .875rem;
    }

    .account-order-items li {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .account-order-product {
        font-weight: 600;
    }

    .account-order-quantity {
        color: #6b7280;
        white-space: nowrap;
    }

    .account-order-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem .5rem;
        color: #6b7280;
        font-size: .76rem;
    }

    .account-order-meta-divider {
        height: 14px;
        margin: 0 .2rem;
        border-left: 1px solid #d1d5db;
    }

    .account-order-badge {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: .2rem .55rem;
        font-size: .7rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .account-order-badge.is-success {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .account-order-badge.is-warning {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .account-order-badge.is-danger {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .account-order-badge.is-info {
        border-color: #bae6fd;
        background: #f0f9ff;
        color: #0369a1;
    }

    .account-order-badge.is-neutral {
        border-color: #e5e7eb;
        background: #f9fafb;
        color: #4b5563;
    }

    .account-order-status-mobile {
        display: none;
    }

    .account-order-status {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: .25rem .6rem;
        font-size: .72rem;
        font-weight: 650;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .account-order-status.is-positive {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .account-order-status.is-pending {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .account-order-status.is-negative {
        border-color: #fecaca;
        background: #fef2f2;
        color: #b91c1c;
    }

    .account-order-status.is-neutral {
        border-color: #e5e7eb;
        background: #f9fafb;
        color: #4b5563;
    }

    .account-order-contact-mobile {
        display: none;
    }

    .account-order-side {
        display: flex;
        flex: 0 0 auto;
        align-items: flex-start;
        gap: 20px;
        padding: 4px 8px 4px 0;
        text-align: right;
    }

    .account-order-summary {
        display: flex;
        flex: 0 1 auto;
        flex-direction: column;
        align-items: flex-end;
        gap: .35rem;
        max-width: 240px;
        text-align: right;
    }

    .account-order-total {
        color: #111827;
        font-size: 1rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .account-order-actions {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-top: .15rem;
    }

    .account-order-menu-column {
        flex: 0 0 34px;
    }

    .account-order-details {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: #15803d;
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .account-order-details:hover {
        color: #166534;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .account-order-menu-button {
        display: inline-flex;
        width: 34px;
        height: 34px;
        align-items: center;
        justify-content: center;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #4b5563;
        font-size: .95rem;
        transition: background-color .15s ease, border-color .15s ease;
    }

    .account-order-menu-button::after {
        display: none;
    }

    .account-order-menu-button:hover,
    .account-order-menu-button:focus-visible {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .account-order-menu {
        min-width: 190px;
        padding: .35rem;
        border-color: #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 10px 24px rgba(17, 24, 39, .12);
    }

    .account-order-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: .55rem;
        border-radius: 7px;
        padding: .55rem .65rem;
        color: #374151;
        font-size: .82rem;
        text-align: left;
        white-space: normal;
    }

    .account-order-menu .dropdown-item:hover,
    .account-order-menu .dropdown-item:focus {
        background: #f0fdf4;
        color: #15803d;
    }

    .account-order-menu form {
        margin: 0;
    }

    @media (max-width: 767.98px) {
        .account-order-list {
            display: grid;
            gap: .65rem;
            border-top: 0;
        }

        .account-order-row {
            align-items: stretch;
            flex-direction: column;
            gap: .75rem;
            min-width: 0;
            padding: .85rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fff;
        }

        .account-order-main { width: 100%; }

        .account-order-heading {
            align-items: flex-start;
            justify-content: space-between;
            gap: .35rem .65rem;
        }

        .account-order-reference {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .account-order-date {
            flex: 0 0 auto;
            padding-top: .12rem;
        }

        .account-order-status-mobile {
            display: block;
            margin-top: .4rem;
        }

        .account-order-items {
            display: grid;
            gap: .35rem;
            margin: .65rem 0;
            font-size: .84rem;
        }

        .account-order-items li {
            overflow-wrap: anywhere;
            line-height: 1.4;
        }

        .account-order-contact-mobile {
            display: grid;
            gap: .3rem;
            margin: 0 0 .6rem;
            color: #6b7280;
            font-size: .76rem;
        }

        .account-order-contact-mobile span {
            align-items: flex-start;
            display: flex;
            gap: .4rem;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .account-order-contact-mobile i {
            flex: 0 0 auto;
            color: #15803d;
            margin-top: .1rem;
        }

        .account-order-meta {
            gap: .35rem;
            padding-top: .55rem;
            border-top: 1px solid #f1f3f2;
        }

        .account-order-side {
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: none;
            gap: .75rem;
            padding: 0;
        }

        .account-order-summary {
            align-items: flex-start;
            max-width: none;
            min-width: 0;
            text-align: left;
        }

        .account-order-total {
            font-size: .98rem;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .account-order-details {
            min-height: 40px;
            align-items: center;
        }

        .account-order-menu-column {
            align-self: flex-end;
            flex: 0 0 40px;
            position: relative;
        }

        .account-order-menu-button {
            width: 40px;
            height: 40px;
        }

        .account-order-menu {
            min-width: min(220px, calc(100vw - 48px));
            max-width: calc(100vw - 32px);
        }

        .account-order-menu .dropdown-item {
            min-height: 42px;
            padding: .65rem .7rem;
        }

        .account-orders .pagination {
            display: flex;
            flex-wrap: wrap;
            gap: .25rem;
            margin-bottom: 0;
        }

        .account-orders .page-link {
            min-width: 40px;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }
</style>
@endsection
