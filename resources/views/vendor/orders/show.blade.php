@extends('front.layouts.admin')
@section('page-title', 'Order '.$order->reference)

@push('styles')
<style>
    .vendor-order-detail { color: #0f172a; }
    .vendor-order-detail .detail-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .vendor-order-detail .detail-label { color: #64748b; font-size: .76rem; font-weight: 500; }
    .vendor-order-detail .detail-value { color: #0f172a; font-size: .9rem; }
    .vendor-order-detail .item-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px;
    }
    .vendor-order-detail .item-card + .item-card { margin-top: 12px; }
    .vendor-order-detail .status-chip {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        color: #1e40af;
        display: inline-flex;
        font-size: .72rem;
        font-weight: 600;
        padding: 4px 9px;
        white-space: nowrap;
    }
    .vendor-order-detail .status-chip-complete {
        background: #ecfdf5;
        border-color: #a7f3d0;
        color: #047857;
    }
    .vendor-order-detail .item-actions { align-items: flex-start; }
    .vendor-order-detail .problem-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        padding: 12px;
        width: 100%;
    }
    .vendor-order-detail .btn-primary {
        background: #2563EB;
        border-color: #2563EB;
    }
    .vendor-order-detail .btn-primary:hover, .vendor-order-detail .btn-primary:focus-visible {
        background: #1D4ED8;
        border-color: #1D4ED8;
    }
    .vendor-order-detail .form-control:focus, .vendor-order-detail .form-select:focus {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
        box-shadow: none;
    }
    .vendor-order-detail .vendor-review-section { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; }
    .vendor-order-detail .vendor-review-heading { display: flex; gap: 12px; align-items: flex-start; }
    .vendor-order-detail .vendor-review-icon { align-items: center; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; color: #2563eb; display: inline-flex; flex: 0 0 36px; height: 36px; justify-content: center; }
    .vendor-order-detail .vendor-decision-grid { display: grid; gap: 12px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 16px; }
    .vendor-order-detail .vendor-decision-choice { align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; gap: 12px; min-height: 72px; padding: 12px; text-align: left; }
    .vendor-order-detail .vendor-decision-choice strong, .vendor-order-detail .vendor-decision-choice small { display: block; }
    .vendor-order-detail .vendor-decision-choice small { color: #64748b; margin-top: 2px; }
    .vendor-order-detail .vendor-decision-choice.is-selected { border-color: #2563eb; box-shadow: 0 0 0 2px rgba(37,99,235,.12); }
    .vendor-order-detail .vendor-cancel-choice.is-selected { border-color: #b91c1c; box-shadow: 0 0 0 2px rgba(185,28,28,.12); }
    .vendor-order-detail .vendor-decision-icon { align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; display: inline-flex; flex: 0 0 34px; height: 34px; justify-content: center; }
    .vendor-order-detail .vendor-review-actions { align-items: center; display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
    .vendor-order-detail .vendor-payment-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; }
    .vendor-order-detail .vendor-segmented-control { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vendor-order-detail .vendor-segmented-control label { cursor: pointer; }
    .vendor-order-detail .vendor-segmented-control input, .vendor-order-detail .vendor-payment-options input { position: absolute; opacity: 0; }
    .vendor-order-detail .vendor-segmented-control span { border: 1px solid #cbd5e1; display: block; padding: 8px 12px; text-align: center; }
    .vendor-order-detail .vendor-segmented-control label:first-child span { border-radius: 8px 0 0 8px; }
    .vendor-order-detail .vendor-segmented-control label:last-child span { border-left: 0; border-radius: 0 8px 8px 0; }
    .vendor-order-detail .vendor-segmented-control input:checked + span { background: #eff6ff; border-color: #2563eb; color: #1d4ed8; }
    .vendor-order-detail .vendor-payment-options { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .vendor-order-detail .vendor-payment-options label { cursor: pointer; }
    .vendor-order-detail .vendor-payment-options span { align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; gap: 8px; height: 100%; padding: 10px; }
    .vendor-order-detail .vendor-payment-options input:checked + span { background: #eff6ff; border-color: #2563eb; color: #1d4ed8; }
    .vendor-order-detail .vendor-balance-summary { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; }
    .vendor-order-detail .vendor-balance-summary div { align-items: center; display: flex; justify-content: space-between; padding: 9px 12px; }
    .vendor-order-detail .vendor-balance-summary div + div { border-top: 1px solid #e2e8f0; }
    .vendor-order-detail .vendor-balance-summary dt { color: #64748b; font-size: .82rem; }
    .vendor-order-detail .vendor-balance-summary dd { font-weight: 700; margin: 0; }
    .vendor-order-detail .vendor-products-table { table-layout: fixed; }
    .vendor-order-detail .vendor-products-table th { color: #475569; font-size: .75rem; }
    .vendor-order-detail .vendor-products-table td { font-size: .875rem; }
    @media (max-width: 575.98px) {
        .vendor-order-detail .vendor-decision-grid, .vendor-order-detail .vendor-payment-options { grid-template-columns: 1fr; }
        .vendor-order-detail .vendor-review-actions { align-items: stretch; flex-direction: column-reverse; }
        .vendor-order-detail .vendor-review-actions .btn { width: 100%; }
        .vendor-order-detail .vendor-products-table thead { display: none; }
        .vendor-order-detail .vendor-products-table tbody, .vendor-order-detail .vendor-products-table tr, .vendor-order-detail .vendor-products-table td { display: block; width: 100%; }
        .vendor-order-detail .vendor-products-table tr { border-bottom: 1px solid #e2e8f0; padding: 10px 0; }
        .vendor-order-detail .vendor-products-table td { border: 0; padding: 4px 12px; }
        .vendor-order-detail .vendor-products-table td:not(:first-child) { text-align: left !important; }
        .vendor-order-detail .vendor-products-table td:not(:first-child)::before { content: attr(data-label); color: #64748b; display: inline-block; font-size: .72rem; font-weight: 600; margin-right: 8px; }
        .vendor-order-detail .vendor-products-table tfoot { display: none; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 vendor-order-detail">
    @include('marketplace.partials.flash')

    <a href="{{ route('vendor.orders.index') }}" class="text-decoration-none">&larr; Back to orders</a>
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mt-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Review Order</h1>
            <div class="text-muted small">{{ $order->reference }} · Placed {{ $order->created_at?->format('M j, Y \a\t g:i A') }}</div>
        </div>
        <span class="status-chip">{{ $order->fulfillment_status === 'ready_for_dispatch' ? 'Ready for Delivery' : \Illuminate\Support\Str::headline($order->fulfillment_status) }}</span>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <section class="detail-panel overflow-hidden h-100" aria-labelledby="vendor-order-products">
                <div class="p-3 border-bottom">
                    <h2 id="vendor-order-products" class="h5 mb-0">Ordered products</h2>
                </div>
                <div class="table-responsive">
                    <table class="table vendor-products-table align-middle mb-0">
                        <thead><tr><th>Product</th><th>Quantity</th><th class="text-end">Unit price</th><th class="text-end">Subtotal</th></tr></thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td><strong>{{ $item->product_name }}</strong>@if($item->variation_label && $item->variation_value)<div class="small text-muted">{{ $item->variation_label }}: {{ $item->variation_value }}</div>@endif @if($item->sku)<div class="small text-muted">SKU: {{ $item->sku }}</div>@endif</td>
                                    <td data-label="Quantity">{{ number_format($item->quantity) }}</td>
                                    <td data-label="Unit price" class="text-end">{{ number_format((float) $item->unit_price, 2, '.', ',') }} RWF</td>
                                    <td data-label="Subtotal" class="text-end fw-semibold">{{ number_format((float) $item->line_total, 2, '.', ',') }} RWF</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr><th colspan="3" class="text-end">Assigned total</th><th class="text-end">{{ number_format((float) $items->sum('line_total'), 2, '.', ',') }} RWF</th></tr></tfoot>
                    </table>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="detail-panel p-3 h-100" aria-labelledby="vendor-order-information">
                <h2 id="vendor-order-information" class="h5 mb-3">Customer and order information</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4 detail-label">Customer</dt><dd class="col-sm-8 detail-value">{{ $order->customer_name ?: 'Not provided' }}</dd>
                    <dt class="col-sm-4 detail-label">Phone</dt><dd class="col-sm-8 detail-value">{{ $order->phone ?: 'Not provided' }}</dd>
                    <dt class="col-sm-4 detail-label">Email</dt><dd class="col-sm-8 detail-value">{{ $order->email ?: 'Not provided' }}</dd>
                    <dt class="col-sm-4 detail-label">Address</dt><dd class="col-sm-8 detail-value">{{ $order->delivery_address ?: 'Not provided' }}</dd>
                    <dt class="col-sm-4 detail-label">Order total</dt><dd class="col-sm-8 detail-value fw-semibold">{{ number_format((float) $order->total, 2, '.', ',') }} RWF</dd>
                </dl>
                @if($order->delivery_notes)<div class="small text-muted mt-2"><strong>Customer notes:</strong> {{ $order->delivery_notes }}</div>@endif
            </section>
        </div>
    </div>

    @include('vendor.orders.partials.vendor-decision')

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <section class="detail-panel p-3 h-100" aria-labelledby="fulfillment-items-title">
                <h2 id="fulfillment-items-title" class="h5 mb-3">Your assigned items</h2>
                @foreach($items as $item)
                    @php
                        $allowedTransitions = config('marketplace.vendor_item_transitions.'.$item->fulfillment_status, []);
                        $terminalItem = in_array($item->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true);
                        $orderAccepted = $order->fulfillment_status !== 'pending_confirmation';
                        $terminalOrder = in_array($order->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true);
                        $canRespond = $orderAccepted && ! $terminalOrder && ! $terminalItem
                            && $item->assignment_status === 'assigned';
                        $canReportProblem = $orderAccepted && ! $terminalOrder && ! $terminalItem
                            && $item->assignment_status !== 'clarification';
                        $nextStatus = match ($item->fulfillment_status) {
                            'confirmed', 'pending_confirmation' => 'processing',
                            'awaiting_stock' => 'processing',
                            default => null,
                        };
                        $nextAction = match ($nextStatus) {
                            'processing' => $item->fulfillment_status === 'awaiting_stock' ? 'Resume Preparation' : 'Start Preparing',
                            default => null,
                        };
                        $canProgress = $orderAccepted && ! $terminalOrder && ! $terminalItem
                            && $item->assignment_status === 'accepted'
                            && $nextStatus
                            && in_array($nextStatus, $allowedTransitions, true);
                        $assignmentReady = $items->isNotEmpty() && $items->every(fn ($assignedItem) =>
                            $assignedItem->assignment_status === 'accepted'
                            && in_array($assignedItem->fulfillment_status, ['processing', 'ready_for_dispatch'], true)
                        );
                        $assignmentCanCreateDelivery = $items->isNotEmpty() && $items->every(fn ($assignedItem) =>
                            $assignedItem->assignment_status === 'accepted'
                            && $assignedItem->fulfillment_status === 'ready_for_dispatch'
                        );
                        $assignmentDeliveryComplete = $items->isNotEmpty() && $items->every(fn ($assignedItem) =>
                            $order->deliveries->contains('order_item_id', $assignedItem->id)
                        );
                        $assignmentHasProblem = $items->contains(fn ($assignedItem) => $assignedItem->assignment_status === 'clarification');
                        $delivery = $order->deliveries->firstWhere('order_item_id', $item->id);
                        $completionTime = $delivery?->delivered_at ?? $item->updated_at;
                    @endphp
                    <article class="item-card">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <h3 class="h6 mb-1">{{ $item->product_name }}</h3>
                                @if($item->variation_label && $item->variation_value)
                                    <div class="text-muted small">{{ $item->variation_label }}: {{ $item->variation_value }}</div>
                                @endif
                                @if($item->sku)<div class="text-muted small">SKU: {{ $item->sku }}</div>@endif
                                <div class="text-muted small">Quantity: {{ number_format($item->quantity) }} · {{ number_format((float) $item->unit_price, 0, '.', ',') }} RWF each</div>
                            </div>
                            <div class="text-end">
                                <span class="status-chip {{ in_array($item->fulfillment_status, ['delivered', 'completed'], true) ? 'status-chip-complete' : '' }}">{{ $item->fulfillment_status === 'ready_for_dispatch' ? 'Ready for Delivery' : \Illuminate\Support\Str::headline($item->fulfillment_status) }}</span>
                                <div class="text-muted small mt-1">Assignment: {{ $item->assignment_status === 'clarification' ? 'Problem reported' : \Illuminate\Support\Str::headline($item->assignment_status) }}</div>
                            </div>
                        </div>
                        @if($item->location)
                            <div class="small text-muted mt-2">Fulfillment location: {{ $item->location->warehouse_name }}@if($item->location->shelf_reference) · {{ $item->location->shelf_reference }}@endif</div>
                        @endif
                        @if($item->fulfillment_instructions)
                            <div class="small mt-2"><span class="fw-semibold">Instructions:</span> {{ $item->fulfillment_instructions }}</div>
                        @endif
                        @if($item->assignment_status === 'clarification')
                            <p class="small text-warning mt-2 mb-0">A reported problem is awaiting administrative review. This assignment cannot be marked Ready for Delivery.</p>
                        @endif
                        @if(in_array($item->fulfillment_status, ['delivered', 'completed'], true))
                            <div class="small text-muted mt-2"><span class="fw-semibold">Completed:</span> {{ $completionTime?->format('M j, Y \a\t g:i A') ?? 'Date not recorded' }}</div>
                        @endif
                        @if($order->fulfillment_status === 'pending_confirmation')
                            <p class="small text-muted mt-3 mb-0">This customer order is awaiting administrator review. Vendor actions will be available after it is accepted.</p>
                        @elseif($terminalItem || $terminalOrder)
                            <p class="small text-muted mt-3 mb-0">This assignment is closed and available for reference only.</p>
                        @else
                        <div class="d-flex flex-column gap-2 mt-3 item-actions">
                            @if($canRespond)
                                <form method="POST" action="{{ route('vendor.orders.respond', $item) }}" data-orders-live-form onsubmit="if (! confirm('Accept this product assignment for your business?')) return false; this.querySelector('button[type=submit]').disabled = true;">
                                    @csrf
                                    <input type="hidden" name="decision" value="accept">
                                    <button class="btn btn-sm btn-primary" type="submit">Accept Assignment</button>
                                </form>
                                <details class="problem-panel">
                                    <summary class="text-muted fw-semibold">Unable to take this assignment?</summary>
                                    <form method="POST" action="{{ route('vendor.orders.respond', $item) }}" class="mt-3" data-orders-live-form onsubmit="return confirm('Decline this assignment and return it for reassignment?');">
                                        @csrf
                                        <input type="hidden" name="decision" value="decline">
                                        <label for="decline-note-{{ $item->id }}" class="form-label small">Reason (optional)</label>
                                        <textarea id="decline-note-{{ $item->id }}" name="note" rows="2" maxlength="2000" class="form-control form-control-sm" placeholder="Briefly explain why you cannot fulfill it"></textarea>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" type="submit">Decline Assignment</button>
                                    </form>
                                </details>
                            @endif
                            @if($canReportProblem)
                                <details class="problem-panel">
                                    <summary class="text-danger fw-semibold">Report a Problem</summary>
                                    <form method="POST" action="{{ route('vendor.orders.respond', $item) }}" class="mt-3" data-orders-live-form>
                                        @csrf
                                        <input type="hidden" name="decision" value="problem">
                                        <label for="problem-note-{{ $item->id }}" class="form-label small">Describe the issue <span class="text-danger" aria-hidden="true">*</span></label>
                                        <textarea id="problem-note-{{ $item->id }}" name="note" rows="3" maxlength="2000" required class="form-control form-control-sm" placeholder="For example, unavailable stock or a damaged product"></textarea>
                                        <button class="btn btn-sm btn-outline-danger mt-2" type="submit">Send Problem Report</button>
                                    </form>
                                </details>
                            @endif
                            @if($canProgress)
                                <form method="POST" action="{{ route('vendor.orders.progress', $item) }}" class="d-flex flex-wrap gap-2" data-orders-live-form>
                                    @csrf
                                    <input type="hidden" name="fulfillment_status" value="{{ $nextStatus }}">
                                    <button class="btn btn-sm btn-primary" type="submit">{{ $nextAction }}</button>
                                </form>
                            @endif
                            @if($loop->first && $assignmentReady && $items->contains(fn ($assignedItem) => $assignedItem->fulfillment_status === 'processing'))
                                <form method="POST" action="{{ route('vendor.orders.ready-for-delivery', $order->reference) }}" data-orders-live-form onsubmit="return confirm('Confirm that every assigned product and quantity has been checked and is ready for delivery?');">
                                    @csrf
                                    <button class="btn btn-sm btn-primary" type="submit">Ready for Delivery</button>
                                </form>
                            @elseif($loop->first && $assignmentHasProblem)
                                <p class="small text-warning mb-0">Resolve the reported problem before the assignment can be marked Ready for Delivery.</p>
                            @endif
                            @if($loop->first && $assignmentCanCreateDelivery && ! $assignmentDeliveryComplete && ! $terminalOrder)
                                <a class="btn btn-sm btn-primary" href="{{ route('vendor.orders.deliveries.prepare', $order->reference) }}">Prepare Delivery</a>
                            @elseif($loop->first && $assignmentDeliveryComplete)
                                <p class="small text-success mb-0">Delivery created for this vendor assignment. See the Deliveries section for its record.</p>
                            @endif
                        </div>
                        @endif
                    </article>
                @endforeach
                @if($assignmentHistory->isNotEmpty())
                    <section class="mt-4 pt-3 border-top" aria-labelledby="vendor-assignment-history">
                        <h3 id="vendor-assignment-history" class="h6 mb-2">Assignment history</h3>
                        @foreach($assignmentHistory as $entry)
                            @php
                                $historyItem = $items->firstWhere('id', $entry->auditable_id);
                                $historyStatus = $entry->changes['to'] ?? null;
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
                            <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                <div class="fw-semibold">{{ $historyLabel }}@if($historyItem) · {{ $historyItem->product_name }}@endif</div>
                                <div class="small text-muted">{{ $entry->created_at?->format('M j, Y g:i A') }} · {{ $entry->actor?->name ?? 'System' }}</div>
                                @if($entry->action === 'order.item_problem_reported' && data_get($entry->changes, 'note'))
                                    <div class="small mt-1">{{ data_get($entry->changes, 'note') }}</div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </section>
        </div>

        <div class="col-lg-5">
            <section class="detail-panel p-3" aria-labelledby="delivery-progress-title">
                <h2 id="delivery-progress-title" class="h5 mb-3">Delivery progress</h2>
                @forelse($order->deliveries as $delivery)
                    <div class="{{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                        <div class="fw-semibold">{{ $delivery->reference }} · {{ \Illuminate\Support\Str::headline($delivery->status) }}</div>
                        @if($delivery->dispatched_at)
                            <div class="small text-muted">Dispatched {{ $delivery->dispatched_at->format('M j, Y g:i A') }}</div>
                        @endif
                        @if($delivery->delivered_at)
                            <div class="small text-muted">Delivered {{ $delivery->delivered_at->format('M j, Y g:i A') }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">Delivery has not been scheduled yet. Use the Deliveries section for delivery updates when a task is available.</p>
                @endforelse
                <a href="{{ route('vendor.deliveries.index') }}" class="btn btn-sm btn-outline-secondary mt-3">Open Deliveries</a>
            </section>
        </div>
    </div>
</div>
@include('marketplace.orders.partials.live-updates')
@endsection
