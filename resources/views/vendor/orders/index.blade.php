@extends('front.layouts.admin')
@section('page-title', 'Orders')

@push('styles')
<style>
    .orders-page { color: #0f172a; }
    .orders-page .orders-description { color: #64748b; max-width: 760px; }
    .orders-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; }
    .orders-tabs {
        display: flex; flex-wrap: nowrap; gap: 6px; overflow-x: auto; padding: 0 2px 8px;
        scrollbar-width: thin; border-bottom: 1px solid #e2e8f0;
    }
    .orders-tab {
        align-items: center; color: #475569; display: inline-flex; flex: 0 0 auto; gap: 8px;
        padding: 11px 14px; text-decoration: none; border-bottom: 2px solid transparent;
        font-size: .9rem; font-weight: 600; white-space: nowrap;
    }
    .orders-tab:hover, .orders-tab:focus-visible { color: #087f6b; }
    .orders-tab:focus-visible, .orders-page .form-control:focus, .orders-page .form-select:focus {
        outline: 2px solid #2563eb; outline-offset: 2px; box-shadow: none;
    }
    .orders-tab.active { color: #087f6b; border-bottom-color: #00a889; }
    .orders-tab-count {
        min-width: 22px; padding: 2px 6px; border-radius: 999px;
        background: #f1f5f9; color: #475569; font-size: .72rem; text-align: center;
    }
    .orders-tab.active .orders-tab-count { background: #d9f7ef; color: #087f6b; }
    .orders-filter-scroll { overflow-x: auto; }
    .orders-filter-row { align-items: center; display: flex; flex-wrap: nowrap; gap: 8px; min-width: max-content; }
    .orders-filter-row .form-control, .orders-filter-row .form-select, .orders-filter-row .btn {
        min-height: 40px; font-size: .85rem;
    }
    .orders-filter-search { min-width: 220px; width: 270px; }
    .orders-filter-status { min-width: 160px; width: 170px; }
    .orders-filter-payment { min-width: 140px; width: 150px; }
    .orders-filter-date { min-width: 145px; width: 150px; }
    .orders-table { width: 100%; table-layout: fixed; }
    .orders-table thead th {
        background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #64748b;
        font-size: .72rem; font-weight: 600; padding: 9px 10px; white-space: nowrap;
    }
    .orders-table tbody td {
        border-color: #f1f5f9; color: #334155; font-size: .84rem; padding: 10px; vertical-align: middle;
        overflow-wrap: anywhere;
    }
    .orders-table th:nth-child(1), .orders-table td:nth-child(1) { width: 17%; }
    .orders-table th:nth-child(2), .orders-table td:nth-child(2) { width: 22%; }
    .orders-table th:nth-child(3), .orders-table td:nth-child(3) { width: 16%; }
    .orders-table th:nth-child(4), .orders-table td:nth-child(4) { width: 17%; }
    .orders-table th:nth-child(5), .orders-table td:nth-child(5) { width: 14%; }
    .orders-table th:nth-child(6), .orders-table td:nth-child(6) { width: 14%; }
    .orders-table tbody tr:hover { background: #f8fafc; }
    .orders-ref { color: #1d4ed8; font-weight: 700; text-decoration: none; }
    .orders-ref:hover { text-decoration: underline; }
    .orders-secondary { color: #64748b; font-size: .76rem; }
    .orders-status {
        border: 1px solid transparent; border-radius: 999px; display: inline-flex;
        font-size: .72rem; font-weight: 600; padding: 4px 9px; white-space: nowrap;
    }
    .orders-status-neutral { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }
    .orders-status-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .orders-status-info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
    .orders-status-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .orders-status-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .orders-action { white-space: nowrap; }
    .orders-empty-row td { width: auto !important; }
    @media (max-width: 991.98px) {
        .orders-page-header { align-items: flex-start !important; }
        .orders-filter-scroll { margin-right: -12px; padding-right: 12px; }
        .orders-filter-row { min-width: 0; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .orders-filter-row > * { min-width: 0; width: 100%; }
        .orders-filter-search { grid-column: 1 / -1; }
        .orders-page .table-responsive { overflow: visible; }
        .orders-table, .orders-table tbody { display: block; width: 100%; }
        .orders-table thead { display: none; }
        .orders-table tbody tr {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            column-gap: 12px;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .orders-table tbody td {
            display: block;
            min-width: 0;
            width: auto !important;
            padding: 7px 0;
            border: 0;
        }
        .orders-table tbody td[data-label]::before {
            display: block;
            margin-bottom: 3px;
            color: #64748b;
            content: attr(data-label);
            font-size: .68rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .orders-table tbody td[data-label="Order"],
        .orders-table tbody td[data-label="Customer"],
        .orders-table tbody td[data-label="Action"] { grid-column: 1 / -1; }
        .orders-table tbody td[data-label="Action"] .orders-action { width: 100%; }
        .orders-table tbody .orders-empty-row { display: block; }
        .orders-table tbody .orders-empty-row td { display: block; }
    }
</style>
@endpush

@section('content')
@php
    $tabs = [
        'pending' => 'Pending Orders',
        'paid' => 'Paid Orders',
    ];
    $tabQuery = $filters;
    $statusTone = static function ($status) {
        return match ($status) {
            'paid', 'delivered', 'completed', 'returned' => 'success',
            'failed', 'refunded', 'cancelled', 'cancellation_requested', 'return_requested' => 'danger',
            'processing', 'awaiting_stock', 'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'in_transit' => 'info',
            'pending', 'partially_paid', 'pending_confirmation', 'assigned', 'unassigned', 'clarification' => 'warning',
            default => 'neutral',
        };
    };
    $statusLabel = static fn ($status) => $status === 'ready_for_dispatch'
        ? 'Ready for Delivery'
        : \Illuminate\Support\Str::headline($status);
    $eligibleStatuses = ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'];
@endphp
<div class="container-fluid py-4 orders-page">
    @include('marketplace.partials.flash')

    <div class="orders-page-header d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Orders</h1>
            <p class="orders-description mb-0">Manage orders assigned to your business and prepare eligible products for delivery.</p>
        </div>
    </div>

    <section class="orders-panel mb-3" aria-label="Order categories">
        <nav class="orders-tabs" aria-label="Order tabs">
            @foreach($tabs as $key => $label)
                @php $tabQuery['tab'] = $key; @endphp
                <a href="{{ route('vendor.orders.index', $tabQuery) }}"
                   class="orders-tab {{ $tab === $key ? 'active' : '' }}"
                   @if($tab === $key) aria-current="page" @endif>
                    {{ $label }} <span class="orders-tab-count" data-tab-count="{{ $key }}">{{ number_format($tabCounts[$key]) }}</span>
                </a>
            @endforeach
        </nav>
    </section>

    <section class="orders-panel p-3 mb-3" aria-label="Search and filter orders">
        <form method="GET" action="{{ route('vendor.orders.index') }}" class="orders-filter-scroll">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="orders-filter-row">
                <label class="visually-hidden" for="vendor-order-search">Search reference, customer, or assigned product</label>
                <input id="vendor-order-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control orders-filter-search" placeholder="Search reference, customer, or product">

                <label class="visually-hidden" for="vendor-order-status">Order status</label>
                <select id="vendor-order-status" name="status" class="form-select orders-filter-status">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabel($status) }}</option>
                    @endforeach
                </select>

                <label class="visually-hidden" for="vendor-order-payment">Payment status</label>
                <select id="vendor-order-payment" name="payment" class="form-select orders-filter-payment">
                    <option value="">All payments</option>
                    @foreach(['pending', 'partially_paid', 'paid', 'failed', 'refunded'] as $payment)
                        <option value="{{ $payment }}" @selected(($filters['payment'] ?? '') === $payment)>{{ \Illuminate\Support\Str::headline($payment) }}</option>
                    @endforeach
                </select>

                <label class="visually-hidden" for="vendor-order-date-from">From date</label>
                <input id="vendor-order-date-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control orders-filter-date" aria-label="From date">
                <label class="visually-hidden" for="vendor-order-date-to">To date</label>
                <input id="vendor-order-date-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control orders-filter-date" aria-label="To date">

                <button type="submit" class="btn btn-primary px-3">Apply</button>
                <a href="{{ route('vendor.orders.index', ['tab' => $tab]) }}" class="btn btn-outline-secondary px-3">Clear</a>
            </div>
        </form>
    </section>

    <section class="orders-panel overflow-hidden" aria-label="{{ $tabs[$tab] }}">
        <div class="table-responsive">
            <table class="table orders-table align-middle mb-0">
                <thead><tr><th>Order</th><th>Customer</th><th>Vendor subtotal</th><th>Fulfillment</th><th>Payment</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $vendorItems = $order->items;
                            $items = $tab === 'paid'
                                ? $vendorItems->filter(fn ($item) => in_array($item->assignment_status, ['assigned', 'accepted', 'clarification'], true)
                                    && in_array($item->fulfillment_status, $eligibleStatuses, true)
                                    && ! $order->deliveries->contains('order_item_id', $item->id))->values()
                                : $vendorItems->values();
                            $orderUrl = route('vendor.orders.show', $order->reference);
                            $total = (float) $items->sum('line_total');
                            $vendorCanSendToDelivery = $tab === 'paid'
                                && $order->payment_status === 'paid'
                                && $vendorItems->isNotEmpty()
                                && $vendorItems->every(fn ($item) => in_array($item->assignment_status, ['assigned', 'accepted'], true)
                                    && in_array($item->fulfillment_status, $eligibleStatuses, true)
                                    && ! $order->deliveries->contains('order_item_id', $item->id))
                                && ! $order->deliveries->contains(
                                    fn ($delivery) => $delivery->order_item_id === null
                                );
                        @endphp
                        <tr>
                            <td data-label="Order">
                                <span class="orders-ref">{{ $order->reference }}</span>
                                <div class="orders-secondary">{{ $order->created_at?->format('M j, Y') }}</div>
                            </td>
                            <td data-label="Customer"><span class="fw-semibold">{{ $order->customer_name ?: 'Not provided' }}</span></td>
                            <td data-label="Vendor subtotal" class="fw-semibold text-nowrap">{{ number_format($total, 0, '.', ',') }} RWF</td>
                            <td data-label="Fulfillment"><span class="orders-status orders-status-{{ $statusTone($order->fulfillment_status) }}">{{ $statusLabel($order->fulfillment_status) }}</span></td>
                            <td data-label="Payment"><span class="orders-status orders-status-{{ $statusTone($order->payment_status) }}">{{ match ($order->payment_status) {
                                'pending' => 'Awaiting payment',
                                'partially_paid' => 'Partially paid',
                                'failed' => 'Payment failed',
                                default => \Illuminate\Support\Str::headline($order->payment_status),
                            } }}</span></td>
                            <td data-label="Action" class="text-end">
                                @if($tab === 'pending')
                                    <a href="{{ $orderUrl }}" class="btn btn-sm btn-outline-primary orders-action">Review Order</a>
                                @elseif($vendorCanSendToDelivery)
                                    <form method="POST" action="{{ route('vendor.orders.create-delivery', $order->reference) }}" class="d-inline" data-orders-live-form>
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary orders-action">Send to Delivery</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="orders-empty-row"><td colspan="6" class="text-center py-5">
                            <div class="fw-semibold mb-1">No orders found</div>
                            <div class="text-muted small">
                                @if(($filters['q'] ?? '') || ($filters['status'] ?? '') || ($filters['payment'] ?? '') || ($filters['date_from'] ?? '') || ($filters['date_to'] ?? ''))
                                    Try adjusting your search or filters.
                                    <a href="{{ route('vendor.orders.index', ['tab' => $tab]) }}" class="d-inline-block ms-1">Clear filters</a>
                                @else
                                    Orders assigned to your business will appear here.
                                @endif
                            </div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="p-3 border-top">{{ $orders->links() }}</div>
        @endif
    </section>
</div>
@include('marketplace.orders.partials.live-updates')
@endsection
