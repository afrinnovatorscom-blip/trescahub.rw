@extends('front.layouts.admin')
@section('page-title', $reviewMode ? 'Review Order' : $order->reference)

@push('styles')
<style>
    .order-detail { color: #0f172a; }
    .order-detail .detail-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .order-detail .detail-label { color: #64748b; font-size: .76rem; font-weight: 500; }
    .order-detail .detail-value { color: #0f172a; font-size: .9rem; overflow-wrap: anywhere; }
    .order-detail .review-notice {
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        color: #854d0e;
        padding: 12px 16px;
    }
    .order-detail .status-badge {
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e40af;
        display: inline-flex;
        font-size: .74rem;
        font-weight: 600;
        padding: 4px 10px;
        white-space: nowrap;
    }
    .order-detail .order-items-table { min-width: 0; table-layout: fixed; width: 100%; }
    .order-detail .order-items-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        color: #64748b;
        font-size: .72rem;
        font-weight: 600;
        padding: 11px 12px;
        white-space: nowrap;
    }
    .order-detail .order-items-table tbody td {
        border-color: #f1f5f9;
        color: #334155;
        font-size: .84rem;
        padding: 12px;
        vertical-align: middle;
    }
    .order-detail .product-thumbnail {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        height: 48px;
        justify-content: center;
        overflow: hidden;
        width: 48px;
    }
    .order-detail .product-thumbnail img { height: 100%; object-fit: contain; width: 100%; }
    .order-detail .review-decision {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        height: 100%;
        padding: 18px;
    }
    .order-detail .allocation-state {
        border-radius: 999px;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 600;
        padding: 4px 9px;
    }
    .order-detail .allocation-assigned { background: #f0fdf4; color: #166534; }
    .order-detail .allocation-unassigned { background: #fffbeb; color: #92400e; }
    .order-detail .form-control:focus, .order-detail .form-select:focus {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
        box-shadow: none;
    }
    .order-detail .history-item + .history-item { border-top: 1px solid #f1f5f9; }
    .order-detail .review-section {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px;
    }
    .order-detail .review-section-heading { display: flex; align-items: flex-start; gap: 12px; }
    .order-detail .review-section-icon {
        align-items: center;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        border-radius: 9px;
        color: #2563eb;
        display: inline-flex;
        flex: 0 0 38px;
        height: 38px;
        justify-content: center;
    }
    .order-detail .compact-heading { align-items: center; }
    .order-detail .compact-heading .review-section-icon { flex-basis: 34px; height: 34px; }
    .order-detail .allocation-options, .order-detail .decision-options { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 16px; }
    .order-detail .allocation-choice, .order-detail .decision-choice {
        align-items: center;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        color: #334155;
        cursor: pointer;
        display: flex;
        gap: 10px;
        min-height: 74px;
        padding: 12px;
        position: relative;
        text-align: left;
    }
    .order-detail .allocation-choice input { height: 1px; opacity: 0; position: absolute; width: 1px; }
    .order-detail .allocation-choice.is-selected, .order-detail .decision-choice.is-selected { border: 2px solid #2563eb; background: #eff6ff; color: #1e3a8a; }
    .order-detail .allocation-choice-mark, .order-detail .decision-choice-icon { align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: inline-flex; flex: 0 0 34px; height: 34px; justify-content: center; }
    .order-detail .allocation-choice strong, .order-detail .allocation-choice small, .order-detail .decision-choice strong, .order-detail .decision-choice small { display: block; }
    .order-detail .allocation-choice small, .order-detail .decision-choice small { color: #64748b; font-weight: 400; margin-top: 2px; }
    .order-detail .vendor-allocation-field { border-top: 1px solid #e2e8f0; margin-top: 16px; padding-top: 16px; }
    .order-detail .review-actions { align-items: center; display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; }
    .order-detail .decision-options { grid-template-columns: 1fr; }
    .order-detail .decision-choice { min-height: 68px; }
    .order-detail .decision-choice.accept-choice.is-selected { background: #f0fdf4; border-color: #16a34a; color: #166534; }
    .order-detail .cancel-choice.is-selected { background: #fef2f2; border-color: #dc2626; color: #991b1b; }
    .order-detail .cancel-choice.is-selected .decision-choice-icon { color: #dc2626; }
    .order-detail .segmented-control, .order-detail .payment-options { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .order-detail .segmented-control label, .order-detail .payment-options label { margin: 0; }
    .order-detail .segmented-control input, .order-detail .payment-options input { opacity: 0; position: absolute; }
    .order-detail .segmented-control span, .order-detail .payment-options span { border: 1px solid #cbd5e1; border-radius: 8px; display: block; padding: 9px 10px; text-align: center; }
    .order-detail .segmented-control input:checked + span, .order-detail .payment-options input:checked + span { background: #eff6ff; border: 2px solid #2563eb; color: #1e40af; }
    .order-detail .balance-summary { border-top: 1px solid #e2e8f0; font-size: .84rem; }
    .order-detail .balance-summary div { display: flex; justify-content: space-between; gap: 12px; padding-top: 8px; }
    .order-detail .balance-summary dt { color: #64748b; }
    .order-detail .balance-summary dd { font-weight: 600; margin: 0; }
    .admin-review-order { font-family: 'DM Sans', sans-serif; }
    .admin-review-order h1,
    .admin-review-order h2,
    .admin-review-order h3,
    .admin-review-order .review-section-heading strong {
        font-family: 'DM Sans', sans-serif;
    }
    .admin-review-order h1.h3 { font-size: 1.25rem; font-weight: 700; letter-spacing: -.01em; }
    .admin-review-order .h5 { font-size: 1rem; font-weight: 700; }
    .admin-review-order .h6 { font-size: .875rem; font-weight: 700; }
    .admin-review-order .detail-panel,
    .admin-review-order .review-section { border-color: #e8ecf2; border-radius: 10px; box-shadow: 0 1px 2px rgba(15, 23, 42, .03); }
    .admin-review-order .detail-panel { padding: 16px !important; }
    .admin-review-order .detail-label { font-size: .72rem; }
    .admin-review-order .detail-value { font-size: .84rem; }
    .admin-review-order .status-badge {
        border: 0;
        background: #eef2ff;
        color: #4338ca;
        font-size: .68rem;
        line-height: 1.2;
    }
    .admin-review-order .review-notice {
        background: #fffbeb;
        border-color: #fde68a;
        border-radius: 8px;
        font-size: .82rem;
        padding: 10px 12px;
    }
    .admin-review-order .order-items-table thead th {
        font-size: .68rem;
        letter-spacing: .02em;
        padding: 9px 12px;
        text-transform: uppercase;
    }
    .admin-review-order .order-items-table tbody td { font-size: .82rem; padding: 10px 12px; }
    .admin-review-order .review-section { padding: 16px; }
    .admin-review-order .review-section-heading { align-items: center; gap: 10px; }
    .admin-review-order .review-section-icon {
        background: #f1f5f9;
        border: 0;
        border-radius: 8px;
        color: #475569;
        flex-basis: 34px;
        height: 34px;
    }
    .admin-review-order .review-section-heading .text-muted,
    .admin-review-order .review-section + .text-muted { font-size: .78rem; }
    .admin-review-order .allocation-options,
    .admin-review-order .decision-options { gap: 8px; margin-top: 12px; }
    .admin-review-order .allocation-choice,
    .admin-review-order .decision-choice {
        min-height: 62px;
        border-color: #dfe5ed;
        border-radius: 8px;
        padding: 10px;
        transition: border-color .15s ease, background-color .15s ease;
    }
    .admin-review-order .allocation-choice strong,
    .admin-review-order .decision-choice strong { font-size: .84rem; }
    .admin-review-order .allocation-choice small,
    .admin-review-order .decision-choice small { font-size: .72rem; line-height: 1.35; }
    .admin-review-order .allocation-choice.is-selected,
    .admin-review-order .decision-choice.is-selected {
        border-width: 1px;
        border-color: #14b8a6;
        background: #f0fdfa;
        box-shadow: inset 0 0 0 1px #14b8a6;
        color: #115e59;
    }
    .admin-review-order .cancel-choice.is-selected { border-color: #ef4444; background: #fef2f2; box-shadow: inset 0 0 0 1px #ef4444; }
    .admin-review-order .allocation-choice-mark,
    .admin-review-order .decision-choice-icon {
        border: 0;
        background: #f1f5f9;
        border-radius: 7px;
        flex-basis: 32px;
        height: 32px;
    }
    .admin-review-order .allocation-choice.is-selected .allocation-choice-mark,
    .admin-review-order .decision-choice.is-selected .decision-choice-icon { background: #ccfbf1; }
    .admin-review-order .vendor-allocation-field { margin-top: 12px; padding-top: 12px; }
    .admin-review-order .form-label,
    .admin-review-order legend.form-label { color: #334155; font-size: .78rem; font-weight: 600; margin-bottom: 6px; }
    .admin-review-order .form-control,
    .admin-review-order .form-select {
        border-color: #d8dee8;
        border-radius: 7px;
        font-family: 'DM Sans', sans-serif;
        font-size: .82rem;
        min-height: 38px;
    }
    .admin-review-order textarea.form-control { min-height: 82px; }
    .admin-review-order .form-control:focus,
    .admin-review-order .form-select:focus { border-color: #14b8a6; outline: 0; box-shadow: 0 0 0 3px rgba(20, 184, 166, .12); }
    .admin-review-order .segmented-control,
    .admin-review-order .payment-options { gap: 6px; }
    .admin-review-order .segmented-control span,
    .admin-review-order .payment-options span {
        border-color: #dfe5ed;
        border-radius: 7px;
        font-size: .78rem;
        padding: 8px 9px;
    }
    .admin-review-order .segmented-control input:checked + span,
    .admin-review-order .payment-options input:checked + span {
        border-width: 1px;
        border-color: #14b8a6;
        background: #f0fdfa;
        box-shadow: inset 0 0 0 1px #14b8a6;
        color: #115e59;
    }
    .admin-review-order .btn { border-radius: 7px; font-family: 'DM Sans', sans-serif; font-size: .8rem; font-weight: 600; min-height: 38px; }
    .admin-review-order .btn-primary { background: #0f766e; border-color: #0f766e; }
    .admin-review-order .btn-primary:hover,
    .admin-review-order .btn-primary:focus { background: #115e59; border-color: #115e59; }
    .admin-review-order .btn-light { background: #fff; border-color: #dfe5ed; color: #475569; }
    .admin-review-order .btn-outline-secondary { border-color: #dfe5ed; }
    .admin-review-order .balance-summary { font-size: .78rem; }
    .admin-review-order .review-actions { gap: 6px; margin-top: 12px; }
    .admin-review-order .review-decision { border-radius: 8px; }

    @media (max-width: 767.98px) {
        .order-detail .allocation-options { grid-template-columns: 1fr; }
        .order-detail .review-section { padding: 14px; }
        .order-detail .review-actions { align-items: stretch; flex-direction: column-reverse; }
        .order-detail .review-actions .btn { width: 100%; }
        .order-detail .table-responsive { overflow-x: visible; }
        .order-detail .order-items-table,
        .order-detail .order-items-table tbody { display: block; }
        .order-detail .order-items-table thead { display: none; }
        .order-detail .order-items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            gap: 2px 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 8px 12px;
        }
        .order-detail .order-items-table tbody td {
            min-width: 0;
            overflow-wrap: anywhere;
            padding: 6px 0;
            text-align: left !important;
        }
        .order-detail .order-items-table tbody td:first-child { grid-column: 1 / -1; }
        .order-detail .order-items-table tbody td:not(:first-child)::before {
            color: #64748b;
            content: attr(data-label);
            display: block;
            font-size: .68rem;
            font-weight: 600;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .order-detail .order-items-table tfoot { display: none; }
    }
</style>
@endpush

@section('content')
@php
    $statusLabel = str_starts_with((string) $order->cancellation_reason, 'Admin rejection:')
        ? 'Rejected'
        : (str_starts_with((string) $order->cancellation_reason, 'Admin termination:')
            ? 'Admin terminated'
            : ($order->fulfillment_status === 'ready_for_dispatch'
            ? 'Ready for Delivery'
            : \Illuminate\Support\Str::headline($order->fulfillment_status)));
    $hasAllocationAccess = $canManageOrders && auth()->user()->hasPermission('vendors.view');
    $canAllocate = $hasAllocationAccess && ($acceptMode || in_array($order->fulfillment_status, [
        'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
    ], true));
    $terminalOrder = in_array($order->fulfillment_status, ['cancelled', 'returned'], true);
    $customerName = $order->customer?->name ?: $order->customer_name;
    $customerPhone = $order->customer?->phone ?: $order->phone;
    $customerEmail = $order->customer?->email ?: $order->email;
@endphp
<div class="container-fluid py-4 order-detail{{ $reviewMode ? ' admin-review-order' : '' }}">
    @include('marketplace.partials.flash')

    <a href="{{ route('marketplace.orders.index', ['tab' => 'pending']) }}" class="text-decoration-none">
        &larr; Back to {{ $reviewMode ? 'Pending Orders' : 'Orders' }}
    </a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-3">
        <div>
            <h1 class="h3 mb-1">{{ $reviewMode ? 'Review Order' : 'Order Details' }}</h1>
            <div class="text-muted">{{ $order->reference }} · Placed {{ $order->created_at?->format('M j, Y \a\t g:i A') }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="status-badge">{{ $statusLabel }}</span>
            @if(! $reviewMode && $canManageOrders && ! $terminalOrder)
                @if($editing)
                    <a href="{{ route('marketplace.orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">Cancel edit</a>
                @else
                    <a href="{{ route('marketplace.orders.show', ['order' => $order, 'edit' => 1]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                @endif
            @endif
        </div>
    </div>

    @if($reviewMode)
        <div class="review-notice mb-3" role="status">
            <strong>Administrative decision required.</strong>
            Review the complete order, then allocate it to one vendor or keep it under Admin responsibility. Vendor allocation transfers the order directly to that vendor; Admin acceptance and payment apply only to orders handled by Admin.
        </div>
    @endif
    @if($errors->has('has_paid') && ! $allItemsHandledByAdmin)
        <div class="alert alert-danger" role="alert">{{ $errors->first('has_paid') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <section class="detail-panel h-100 p-3" aria-labelledby="customer-information">
                <h2 id="customer-information" class="h5 mb-3">Customer information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4 detail-label">Customer</dt>
                    <dd class="col-sm-8 detail-value">{{ $customerName ?: 'Not provided' }}</dd>
                    <dt class="col-sm-4 detail-label">Phone</dt>
                    <dd class="col-sm-8 detail-value">
                        @if($customerPhone)
                            <a href="tel:{{ $customerPhone }}">{{ $customerPhone }}</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                    <dt class="col-sm-4 detail-label">Email</dt>
                    <dd class="col-sm-8 detail-value">
                        @if($customerEmail)
                            <a href="mailto:{{ $customerEmail }}">{{ $customerEmail }}</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                    <dt class="col-sm-4 detail-label">Delivery address</dt>
                    <dd class="col-sm-8 detail-value">{{ $order->delivery_address ?: 'Not provided' }}</dd>
                    @if($order->delivery_notes)
                        <dt class="col-sm-4 detail-label">Customer notes</dt>
                        <dd class="col-sm-8 detail-value">{{ $order->delivery_notes }}</dd>
                    @endif
                </dl>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="detail-panel h-100 p-3" aria-labelledby="order-summary">
                <h2 id="order-summary" class="h5 mb-3">Order information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5 detail-label">Reference</dt>
                    <dd class="col-sm-7 detail-value">{{ $order->reference }}</dd>
                    <dt class="col-sm-5 detail-label">Order date</dt>
                    <dd class="col-sm-7 detail-value">{{ $order->created_at?->format('M j, Y g:i A') }}</dd>
                    <dt class="col-sm-5 detail-label">Order source</dt>
                    <dd class="col-sm-7 detail-value">{{ match($order->source) {
                        'request_product' => 'Request Product',
                        'cart_checkout' => 'Cart Checkout',
                        default => 'Legacy order',
                    } }}</dd>
                    <dt class="col-sm-5 detail-label">Items</dt>
                    <dd class="col-sm-7 detail-value">{{ number_format($order->items->sum('quantity')) }}</dd>
                    <dt class="col-sm-5 detail-label">Order total</dt>
                    <dd class="col-sm-7 detail-value fw-semibold">{{ number_format((float) $order->total, 0, '.', ',') }} RWF</dd>
                </dl>
                @if($order->cancellation_reason && ! $reviewMode)
                    <div class="small text-muted mt-2">
                        @if(str_starts_with($order->cancellation_reason, 'Admin rejection:'))
                            <strong>Rejection reason:</strong> {{ \Illuminate\Support\Str::after($order->cancellation_reason, 'Admin rejection: ') }}
                        @elseif(str_starts_with($order->cancellation_reason, 'Admin termination:'))
                            <strong>Termination reason:</strong> {{ \Illuminate\Support\Str::after($order->cancellation_reason, 'Admin termination: ') }}
                        @else
                            <strong>Cancellation reason:</strong> {{ $order->cancellation_reason }}
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>

    @if($reviewMode)
        <section class="detail-panel overflow-hidden mb-3" aria-labelledby="review-order-products">
            <div class="p-3 border-bottom">
                <h2 id="review-order-products" class="h5 mb-0">Ordered products</h2>
            </div>
            <div class="table-responsive">
                <table class="table order-items-table align-middle mb-0">
                    <thead><tr><th>Product</th><th>Quantity</th><th class="text-end">Unit price</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td><strong>{{ $item->product_name }}</strong>@if($item->variation_label && $item->variation_value)<div class="small text-muted">{{ $item->variation_label }}: {{ $item->variation_value }}</div>@endif @if($item->sku)<div class="small text-muted">SKU: {{ $item->sku }}</div>@endif</td>
                                <td data-label="Quantity">{{ number_format($item->quantity) }}</td>
                                <td data-label="Unit price" class="text-end">{{ number_format((float) $item->unit_price, 2, '.', ',') }} RWF</td>
                                <td data-label="Subtotal" class="text-end fw-semibold">{{ number_format((float) $item->line_total, 2, '.', ',') }} RWF</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="3" class="text-end">Order total</th><th class="text-end">{{ number_format((float) $order->total, 2, '.', ',') }} RWF</th></tr></tfoot>
                </table>
            </div>
        </section>
    @endif

    @if(! $reviewMode && $canManageOrders && ! $terminalOrder && $editing)
        <form method="POST" action="{{ route('marketplace.orders.save-changes', $order) }}" id="order-changes-form" data-orders-live-form onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <input type="hidden" name="submission_key" value="{{ old('submission_key', \Illuminate\Support\Str::uuid()) }}">
    @endif

    @if($allocationMode)
        @include('marketplace.orders.partials.accept-form')
    @elseif($acceptMode)
        @include('marketplace.orders.partials.admin-decision')
    @else
    <section class="detail-panel overflow-hidden mb-3" aria-labelledby="ordered-products">
        <div class="p-3 border-bottom">
            <h2 id="ordered-products" class="h5 mb-0">Ordered products</h2>
            @if(! $reviewMode && auth()->user()->hasPermission('vendors.view'))
                <div class="small text-muted mt-1">Product allocation · Each item is assigned as a whole to one active vendor. Partial allocation and internal fulfillment are not supported.</div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table order-items-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th class="text-end">Unit price</th>
                        <th class="text-end">Line total</th>
                        @if(! $reviewMode && auth()->user()->hasPermission('vendors.view'))<th>Allocation</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        @php
                            $thumbnail = ltrim(str_replace(['assets/', 'assets/'], '', (string) $item->product?->thumbnail), '/');
                            $thumbnailExists = $thumbnail !== '' && ! str_contains($thumbnail, '..') && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$thumbnail));
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($thumbnailExists)
                                        <div class="product-thumbnail"><img src="{{ asset('assets/'.$thumbnail) }}" alt="{{ $item->product_name }}"></div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        @if($item->variation_label && $item->variation_value)
                                            <div class="small text-muted">{{ $item->variation_label }}: {{ $item->variation_value }}</div>
                                        @endif
                                        @if($item->sku)<div class="small text-muted">SKU: {{ $item->sku }}</div>@endif
                                        <div class="small mt-1">
                                            <span class="text-muted">Fulfillment:</span>
                                            <span class="status-badge">{{ $item->fulfillment_status === 'ready_for_dispatch' ? 'Ready for Delivery' : \Illuminate\Support\Str::headline($item->fulfillment_status) }}</span>
                                            @if($item->assignment_status === 'clarification')
                                                <span class="status-badge">Problem reported</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Quantity">{{ number_format($item->quantity) }}</td>
                            <td class="text-end" data-label="Unit price">{{ number_format((float) $item->unit_price, 0, '.', ',') }} RWF</td>
                            <td class="text-end fw-semibold" data-label="Line total">{{ number_format((float) $item->line_total, 0, '.', ',') }} RWF</td>
                            @if(! $reviewMode && auth()->user()->hasPermission('vendors.view'))
                                <td data-label="Allocation">
                                    @if($editing && $canAllocate && ! $allItemsHandledByAdmin)
                                        <label class="visually-hidden" for="allocation-{{ $item->id }}">Vendor for {{ $item->product_name }}</label>
                                        <select id="allocation-{{ $item->id }}" name="allocations[{{ $item->id }}]" class="form-select form-select-sm">
                                            <option value="unassigned" @selected(old('allocations.'.$item->id, $item->assignment_status === 'admin_handled' ? 'admin' : ($item->vendor_id ?? 'unassigned')) === 'unassigned')>Unassigned</option>
                                            <option value="admin" @selected(old('allocations.'.$item->id, $item->assignment_status === 'admin_handled' ? 'admin' : ($item->vendor_id ?? 'unassigned')) === 'admin')>Handled by Admin</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected((string) old('allocations.'.$item->id, $item->vendor_id) === (string) $vendor->id)>
                                                    {{ $vendor->business_name }}@if(! $item->vendor_id && $item->product?->vendor?->id === $vendor->id) (product vendor)@endif
                                                </option>
                                            @endforeach
                                            @if($item->vendor && ! $vendors->contains('id', $item->vendor_id))
                                                <option value="{{ $item->vendor_id }}" selected disabled>{{ $item->vendor->business_name }} (inactive)</option>
                                            @endif
                                        </select>
                                        <div class="small text-muted mt-1">
                                            {{ $item->vendor_id ? number_format($item->quantity).' allocated, 0 remaining' : '0 allocated, '.number_format($item->quantity).' remaining' }}
                                        </div>
                                    @else
                                        <span>{{ $item->vendor?->business_name ?? ($item->assignment_status === 'admin_handled' ? 'Handled by Admin' : 'Not allocated') }}</span>
                                        <div class="small text-muted">
                                            {{ $item->vendor_id || $item->assignment_status === 'admin_handled' ? number_format($item->quantity).' allocated, 0 remaining' : '0 allocated, '.number_format($item->quantity).' remaining' }}
                                        </div>
                                        @if($editing && ! $canAllocate)
                                            <div class="small text-muted">Allocation is locked in this order state.</div>
                                        @endif
                                    @endif
                                    @if(! $reviewMode && ! $editing && $canManageOrders && auth()->user()->hasPermission('vendors.view') && $item->vendor_id)
                                        @php
                                            $vendorItems = $order->items->where('vendor_id', $item->vendor_id);
                                            $firstVendorItem = $vendorItems->first();
                                            $vendorAssignmentReady = $vendorItems->isNotEmpty() && $vendorItems->every(fn ($vendorItem) =>
                                                $vendorItem->assignment_status === 'accepted'
                                                && in_array($vendorItem->fulfillment_status, ['processing', 'ready_for_dispatch'], true)
                                            );
                                            $vendorAssignmentHasProblem = $vendorItems->contains(fn ($vendorItem) => $vendorItem->assignment_status === 'clarification');
                                            $hasPendingVendorReadyItem = $vendorItems->contains(fn ($vendorItem) => $vendorItem->fulfillment_status === 'processing');
                                        @endphp
                                        @if($firstVendorItem?->id === $item->id)
                                            @if($vendorAssignmentReady && $hasPendingVendorReadyItem && ! $terminalOrder)
                                                <form method="POST" action="{{ route('marketplace.orders.vendor-ready-for-delivery', ['order' => $order, 'vendor' => $item->vendor_id]) }}" class="mt-2" data-orders-live-form onsubmit="return confirm('Confirm that every assigned product and quantity has been checked and is ready for delivery?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary">Ready for Delivery</button>
                                                </form>
                                            @elseif($vendorAssignmentHasProblem)
                                                <div class="small text-warning mt-2">Resolve the reported problem before this assignment can be marked Ready for Delivery.</div>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No order items are recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif

    @php
        $adminDeliveryItems = $order->items->filter(fn ($item) => $item->vendor_id === null
            && $item->assignment_status === 'admin_handled'
            && in_array($item->fulfillment_status, ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'], true)
            && ! $order->deliveries->contains('order_item_id', $item->id));
        $adminPreparationItems = $order->items->filter(fn ($item) => $item->vendor_id === null
            && $item->assignment_status === 'admin_handled'
            && $item->fulfillment_status === 'ready_for_dispatch'
            && ! $order->deliveries->contains('order_item_id', $item->id));
    @endphp
    @if($canManageOrders
        && auth()->user()->hasPermission('deliveries.manage')
        && $order->payment_status === 'paid'
        && in_array($order->fulfillment_status, ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'], true)
        && $adminDeliveryItems->isNotEmpty())
        <section class="detail-panel p-3 mb-3" aria-labelledby="admin-delivery-shortcut">
            <h2 id="admin-delivery-shortcut" class="h5 mb-1">Admin-handled delivery</h2>
            <p class="small text-muted">Send eligible Admin-handled products to Admin Deliveries. Payment is already recorded and remains separate from fulfillment.</p>
            <form method="POST" action="{{ route('marketplace.orders.deliveries.store', $order) }}" data-orders-live-form onsubmit="if (! confirm('Send the eligible Admin-handled products to Admin Deliveries?')) return false; this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <button type="submit" class="btn btn-primary">Send to Delivery</button>
            </form>
        </section>
    @endif
    @if($canManageOrders
        && auth()->user()->hasPermission('deliveries.manage')
        && ! in_array($order->fulfillment_status, ['pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned'], true)
        && $adminPreparationItems->isNotEmpty())
        <div class="mb-3">
            <a href="{{ route('marketplace.orders.deliveries.prepare', $order) }}" class="btn btn-primary">Prepare Delivery</a>
        </div>
    @endif

    @if(! $reviewMode
        && ! $editing
        && $canManageOrders
        && auth()->user()->hasPermission('vendors.view')
        && $deliveryRequestAssignments->isNotEmpty()
        && ! in_array($order->fulfillment_status, ['pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned'], true))
        @php
            $requestableDeliveryAssignments = $deliveryRequestAssignments
                ->filter(fn ($assignment) => ! $assignment['requested'] && ! $assignment['has_delivery']);
        @endphp
        <section class="detail-panel p-3 mb-3" aria-labelledby="delivery-request-title">
            <h2 id="delivery-request-title" class="h5 mb-2">Request delivery from a vendor</h2>
            <p class="small text-muted mb-3">Select an allocated vendor assignment. The vendor will receive a notification and create the delivery for its own products. This does not create a delivery or change the customer order status.</p>
            @if($requestableDeliveryAssignments->isNotEmpty())
                <form method="POST" action="{{ route('marketplace.orders.delivery-request', $order) }}" class="d-flex flex-wrap align-items-end gap-2" data-orders-live-form>
                    @csrf
                    <div class="flex-grow-1">
                        <label for="delivery-request-vendor" class="form-label small">Vendor assignment</label>
                        <select id="delivery-request-vendor" name="vendor_id" class="form-select" required>
                            <option value="">Select a vendor assignment</option>
                            @foreach($requestableDeliveryAssignments as $assignment)
                                <option value="{{ $assignment['vendor_id'] }}" @selected((string) old('vendor_id') === (string) $assignment['vendor_id'])>
                                    {{ $assignment['vendor_name'] }} · {{ $assignment['product_count'] }} {{ \Illuminate\Support\Str::plural('product', $assignment['product_count']) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Request Delivery</button>
                </form>
            @else
                <p class="small text-muted mb-0">A delivery request has already been sent or a delivery record already exists for each vendor assignment.</p>
            @endif
            @foreach($deliveryRequestAssignments->filter(fn ($assignment) => $assignment['requested'] || $assignment['has_delivery']) as $assignment)
                <div class="small mt-2">
                    <strong>{{ $assignment['vendor_name'] }}:</strong>
                    {{ $assignment['has_delivery'] ? 'Delivery created' : 'Delivery requested' }}
                </div>
            @endforeach
        </section>
    @endif

    @if($reviewMode)
        @if($canManageOrders)
            <section class="detail-panel p-3" aria-labelledby="review-decision-title">
                <h2 id="review-decision-title" class="h5 mb-1">Review Decision</h2>
                <p class="text-muted small mb-3">
                    @if($allocationMode)
                        Choose one responsibility for the entire order and save the allocation before proceeding.
                    @elseif($acceptMode)
                        Confirm that Admin will handle the order. Payment is recorded separately after acceptance.
                    @elseif($hasSavedAllocation)
                        The entire order is allocated to Admin. You may now accept or reject it.
                    @else
                        Review the customer information, requested products, and order total, then allocate the entire order before accepting or rejecting it.
                    @endif
                </p>
                @if($allocationMode)
                    @error('order_allocation')<div class="alert alert-danger">{{ $message }}</div>@enderror
                @elseif(! $hasSavedAllocation)
                    <a href="{{ route('marketplace.orders.show', ['order' => $order, 'allocate' => 1]) }}" class="btn btn-primary">Allocate Entire Order</a>
                @else
                    <div class="row g-3">
                        @unless($acceptMode)
                            <div class="col-md-6">
                                <div class="review-decision">
                                    <h3 class="h6">Accept Order</h3>
                                    <p class="small text-muted">Confirm Admin responsibility. Payment will not be recorded as part of acceptance.</p>
                                    <a href="{{ route('marketplace.orders.show', ['order' => $order, 'accept' => 1]) }}" class="btn btn-primary">Accept Order</a>
                                </div>
                            </div>
                        @endunless
                        <div class="{{ $acceptMode ? 'col-12' : 'col-md-6' }}">
                            <div class="review-decision">
                                <h3 class="h6">Reject Order</h3>
                                <p class="small text-muted">Decline the order with a required reason.</p>
                                <button type="button" class="btn btn-outline-danger" id="show-rejection-form" aria-controls="rejection-panel" aria-expanded="{{ $errors->has('reason') ? 'true' : 'false' }}">Reject Order</button>
                                <div id="rejection-panel" class="mt-3" @unless($errors->has('reason')) hidden @endunless>
                                    <div class="alert alert-warning py-2 small" role="alert">The reason is saved with the order and recorded in its history.</div>
                                    <form method="POST" action="{{ route('marketplace.orders.review', $order) }}" data-orders-live-form onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                        @csrf
                                        <input type="hidden" name="decision" value="reject">
                                        <label for="rejection-reason" class="form-label">Rejection reason <span class="text-danger" aria-hidden="true">*</span></label>
                                        <textarea id="rejection-reason" name="reason" rows="3" maxlength="230" required class="form-control mb-3" aria-required="true" placeholder="Explain why this order is being rejected">{{ old('reason') }}</textarea>
                                        @error('reason')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" id="cancel-rejection">Cancel</button>
                                        <button type="submit" class="btn btn-danger">Confirm Rejection</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        @else
            <div class="alert alert-info">This order is awaiting an administrative review decision.</div>
        @endif
    @else
        @if($canManageOrders && ! $terminalOrder && $allItemsHandledByAdmin)
            <section class="detail-panel p-3 mb-3" aria-labelledby="payment-verification-title">
                <h2 id="payment-verification-title" class="h5 mb-1">Payment information</h2>
                <p class="small text-muted">Payment is independent of fulfillment and allocation. Existing receipts are never overwritten.</p>
                <dl class="row mb-3">
                    <dt class="col-sm-3 detail-label">Payment status</dt>
                    <dd class="col-sm-3 detail-value">{{ \Illuminate\Support\Str::headline($order->payment_status) }}</dd>
                    <dt class="col-sm-3 detail-label">Payment method</dt>
                    <dd class="col-sm-3 detail-value">{{ $order->payment_method ? \Illuminate\Support\Str::headline($order->payment_method) : '—' }}</dd>
                    <dt class="col-sm-3 detail-label">Order total</dt>
                    <dd class="col-sm-3 detail-value">{{ number_format((float) $order->total, 2, '.', ',') }} RWF</dd>
                    <dt class="col-sm-3 detail-label">Amount paid</dt>
                    <dd class="col-sm-3 detail-value">{{ number_format($amountPaid, 2, '.', ',') }} RWF</dd>
                    <dt class="col-sm-3 detail-label">Outstanding balance</dt>
                    <dd class="col-sm-3 detail-value fw-semibold">{{ number_format($outstandingBalance, 2, '.', ',') }} RWF</dd>
                </dl>

                @if($order->payments->isNotEmpty())
                    <h3 class="h6 mt-3">Payment history</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <thead><tr><th>Date</th><th>Type</th><th>Method</th><th class="text-end">Amount</th><th>Recorded by</th></tr></thead>
                            <tbody>
                                @foreach($order->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->created_at?->format('M j, Y g:i A') }}</td>
                                        <td>{{ \Illuminate\Support\Str::headline($payment->type) }}</td>
                                        <td>{{ $payment->method === 'cash_on_delivery' ? 'Cash' : \Illuminate\Support\Str::headline($payment->method) }}</td>
                                        <td class="text-end">{{ number_format((float) $payment->amount, 2, '.', ',') }} RWF</td>
                                        <td>{{ $payment->recorder?->name ?? 'Former staff account' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($order->payment_status === 'paid')
                    <p class="small text-muted">This order was marked paid before payment receipts were tracked. Its saved status is preserved.</p>
                @endif

                @if($editing)
                    <div class="border-top pt-3">
                        <h3 class="h6">Record a payment</h3>
                        <p class="small text-muted">Choose Yes only when recording a new receipt now. Previously recorded payments remain unchanged.</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="has-paid" class="form-label">Has the customer paid?</label>
                                <select id="has-paid" name="has_paid" form="order-changes-form" class="form-select" data-outstanding-balance="{{ number_format($outstandingBalance, 2, '.', '') }}" required>
                                    <option value="no" @selected(old('has_paid', 'no') === 'no')>No, not paid</option>
                                    <option value="yes" @selected(old('has_paid') === 'yes') @disabled($outstandingBalance <= 0)>Yes, paid</option>
                                </select>
                                @error('has_paid')<div class="text-danger small">{{ $message }}</div>@enderror
                                @if($outstandingBalance <= 0)
                                    <div class="small text-muted mt-1">The order is fully paid; no additional payment can be recorded.</div>
                                @endif
                            </div>
                            <div class="col-12 payment-details" hidden>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="payment-type" class="form-label">Payment choice</label>
                                        <select id="payment-type" name="payment_type" form="order-changes-form" class="form-select">
                                            <option value="full" @selected(old('payment_type', 'full') === 'full')>Yes — Fully Paid</option>
                                            <option value="partial" @selected(old('payment_type') === 'partial')>Yes — Partially Paid</option>
                                        </select>
                                        @error('payment_type')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="payment-method" class="form-label">Payment method</label>
                                        <select id="payment-method" name="payment_method" form="order-changes-form" class="form-select">
                                            <option value="cash_on_delivery" @selected(old('payment_method', $order->payment_method) === 'cash_on_delivery')>Cash</option>
                                            <option value="bank_transfer" @selected(old('payment_method', $order->payment_method) === 'bank_transfer')>Bank transfer</option>
                                        </select>
                                        @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4 partial-payment-amount" hidden>
                                        <label for="partial-amount" class="form-label">Amount Paid (RF)</label>
                                        <input id="partial-amount" name="partial_amount" form="order-changes-form" type="number" min="0.01" max="{{ number_format(max(0, round($outstandingBalance - 0.01, 2)), 2, '.', '') }}" step="0.01" value="{{ old('partial_amount') }}" class="form-control">
                                        @error('partial_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                        <div class="small text-muted mt-1">Outstanding balance: {{ number_format($outstandingBalance, 2, '.', ',') }} RWF</div>
                                    </div>
                                </div>
                                <p class="small text-muted mt-2 mb-0">Full payment records the outstanding balance. Payment method records what the administrator reports; it does not independently verify funds received.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if($editing && $canManageOrders && ! $terminalOrder)
            <div class="d-flex justify-content-end mb-3">
                <button type="submit" form="order-changes-form" class="btn btn-primary px-4">Save Changes</button>
            </div>
        @endif

        @if($order->deliveries->isNotEmpty())
            <section class="detail-panel p-3 mb-3" aria-labelledby="delivery-records-title">
                <h2 id="delivery-records-title" class="h5 mb-3">Delivery records</h2>
                @foreach($order->deliveries as $delivery)
                    <div class="d-flex flex-wrap justify-content-between gap-2 py-2 border-top">
                        <span><strong>{{ $delivery->reference }}</strong> · {{ \Illuminate\Support\Str::headline($delivery->status) }}</span>
                        <span class="small text-muted">
                            @if($delivery->delivered_at) Delivered {{ $delivery->delivered_at->format('M j, Y g:i A') }}
                            @elseif($delivery->dispatched_at) Dispatched {{ $delivery->dispatched_at->format('M j, Y g:i A') }}
                            @else Not dispatched @endif
                        </span>
                    </div>
                @endforeach
                <a href="{{ route('marketplace.deliveries.index') }}" class="btn btn-sm btn-outline-secondary mt-2">Open Deliveries</a>
            </section>
        @endif

        @if($editing && $canManageOrders && ! $terminalOrder)
            </form>
        @endif
    @endif

    @if($order->histories->isNotEmpty() || $assignmentHistory->isNotEmpty())
        <section class="detail-panel p-3" aria-labelledby="order-history-title">
            @if($order->histories->isNotEmpty())
                <h2 id="order-history-title" class="h5 mb-2">Order history</h2>
                @foreach($order->histories as $history)
                    <div class="history-item py-2">
                        <div class="fw-semibold">
                            {{ $history->from_status ? ($history->from_status === 'ready_for_dispatch' ? 'Ready for Delivery' : \Illuminate\Support\Str::headline($history->from_status)).' → ' : '' }}
                            {{ $history->to_status === 'ready_for_dispatch' ? 'Ready for Delivery' : (str_starts_with((string) $history->note, 'Admin rejection:') ? 'Rejected' : \Illuminate\Support\Str::headline($history->to_status)) }}
                        </div>
                        <div class="small text-muted">{{ $history->created_at?->format('M j, Y g:i A') }} · {{ $history->actor?->name ?? 'System' }}</div>
                        @if($history->note)
                            <div class="small mt-1">
                                @if(str_starts_with($history->note, 'Admin rejection:'))
                                    <strong>Rejection reason:</strong> {{ \Illuminate\Support\Str::after($history->note, 'Admin rejection:') }}
                                @else
                                    <strong>Internal note:</strong> {{ $history->note }}
                                @endif
                            </div>
                        @endif
                        @if($history->customer_message)<div class="small mt-1"><strong>Customer update:</strong> {{ $history->customer_message }}</div>@endif
                    </div>
                @endforeach
            @endif
            @if($assignmentHistory->isNotEmpty())
                <h2 class="h5 {{ $order->histories->isNotEmpty() ? 'mt-3' : '' }} mb-2">Vendor assignment history</h2>
                @foreach($assignmentHistory as $entry)
                    @php
                        $historyItem = $order->items->firstWhere('id', $entry->auditable_id);
                        $historyStatus = data_get($entry->changes, 'to');
                        $historyLabel = match ($entry->action) {
                            'order.item_allocated' => 'Assignment updated',
                            'order.item_accepted' => 'Assignment accepted',
                            'order.item_declined' => 'Assignment declined',
                            'order.item_problem_reported' => 'Problem reported',
                            'order.item_progress' => $historyStatus === 'ready_for_dispatch'
                                ? 'Ready for Delivery'
                                : \Illuminate\Support\Str::headline($historyStatus ?: $entry->action),
                        };
                    @endphp
                    <div class="history-item py-2">
                        <div class="fw-semibold">{{ $historyLabel }}@if($historyItem) · {{ $historyItem->product_name }}@if(auth()->user()->hasPermission('vendors.view') && $historyItem->vendor) · {{ $historyItem->vendor->business_name }}@endif @endif</div>
                        <div class="small text-muted">{{ $entry->created_at?->format('M j, Y g:i A') }} · {{ $entry->actor?->name ?? 'System' }}</div>
                        @if($entry->action === 'order.item_problem_reported' && data_get($entry->changes, 'note'))
                            <div class="small mt-1">{{ data_get($entry->changes, 'note') }}</div>
                        @endif
                    </div>
                @endforeach
            @endif
        </section>
    @endif
</div>
@include('marketplace.orders.partials.live-updates')
@endsection
