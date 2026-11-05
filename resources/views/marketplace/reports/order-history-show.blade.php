@extends('front.layouts.admin')
@section('page-title', 'Order Historical Record')

@push('styles')
<style>
    .order-record-page { max-width: 1500px; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .order-record-page .text-muted { color: #64748b !important; }
    .record-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
    .record-card-header { display: flex; align-items: center; gap: 9px; padding: 14px 18px; border-bottom: 1px solid #e2e8f0; font-size: 15px; font-weight: 700; }
    .record-card-body { padding: 16px 18px; }
    .record-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 15px 20px; margin: 0; }
    .record-field dt { color: #64748b; font-size: 12px; font-weight: 600; margin-bottom: 4px; }
    .record-field dd { color: #0f172a; font-size: 14px; margin: 0; overflow-wrap: anywhere; }
    .record-status { display: inline-flex; align-items: center; padding: 4px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #475569; }
    .record-status.positive { color: #166534; background: #dcfce7; }
    .record-status.warning { color: #92400e; background: #fef3c7; }
    .record-status.danger { color: #991b1b; background: #fee2e2; }
    .record-table { min-width: 760px; }
    .record-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; white-space: nowrap; padding: 11px 12px; }
    .record-table td { color: #334155; font-size: 13px; padding: 11px 12px; vertical-align: middle; border-color: #e2e8f0; }
    .record-product { display: flex; align-items: center; gap: 10px; min-width: 190px; }
    .record-product-media { position: relative; display: inline-flex; width: 42px; height: 42px; border-radius: 7px; background: #f1f5f9; flex: 0 0 42px; overflow: hidden; }
    .record-product-image, .record-product-placeholder { position: absolute; inset: 0; width: 42px; height: 42px; border-radius: 7px; object-fit: cover; }
    .record-product-placeholder { display: inline-flex; align-items: center; justify-content: center; color: #64748b; }
    .record-total-row td { background: #f8fafc; font-weight: 700; }
    .record-timeline { display: grid; gap: 0; }
    .record-event { position: relative; display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 12px; padding-bottom: 18px; }
    .record-event:last-child { padding-bottom: 0; }
    .record-event-marker { position: relative; display: flex; justify-content: center; color: #2563eb; }
    .record-event-marker::after { position: absolute; top: 16px; bottom: -3px; width: 1px; content: ""; background: #e2e8f0; }
    .record-event:last-child .record-event-marker::after { display: none; }
    .record-event-title { font-size: 13px; font-weight: 600; }
    .record-event-time { color: #64748b; font-size: 12px; margin-top: 2px; }
    .record-event-detail { color: #475569; font-size: 12px; margin-top: 3px; overflow-wrap: anywhere; }
    @media (max-width: 575.98px) { .record-fields { grid-template-columns: 1fr; } .record-card-body { padding: 14px; } }
</style>
@endpush

@section('content')
@php
    $statusClass = match ($order->fulfillment_status) {
        'confirmed', 'processing', 'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'delivered', 'completed' => 'positive',
        'pending_confirmation', 'awaiting_stock', 'cancellation_requested' => 'warning',
        'cancelled', 'returned' => 'danger',
        default => '',
    };
    $imageUrl = fn ($path) => $path
        ? asset('assets/'.preg_replace('#^(public/)?assets/#', '', ltrim((string) $path, '/')))
        : null;
    $acceptedLogs = $auditLogs->where('action', 'order.item_accepted');
    $declinedLogs = $auditLogs->where('action', 'order.item_declined');
    $customerConfirmationLogs = $auditLogs->filter(fn ($log) => $log->action === 'delivery.completed' && data_get($log->meta, 'actor_type') === 'customer');
@endphp
<div class="container-fluid py-3 order-record-page">
    <header class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Marketplace / Reports / Orders History / {{ $order->reference }}</div>
            <h1 class="h3 fw-bold mb-1">Order Historical Record</h1>
            <p class="text-muted small mb-0">Read-only record. No order-processing actions are available here.</p>
        </div>
        <a href="{{ route('marketplace.reports.orders-history') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Orders History
        </a>
    </header>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <section class="record-card h-100">
                <h2 class="record-card-header mb-0"><i class="bi bi-receipt text-primary"></i>Order Information</h2>
                <div class="record-card-body">
                    <dl class="record-fields">
                        <div class="record-field"><dt>Order reference</dt><dd class="fw-semibold">{{ $order->reference }}</dd></div>
                        <div class="record-field"><dt>Current status</dt><dd><span class="record-status {{ $statusClass }}">{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</span></dd></div>
                        <div class="record-field"><dt>Original order date</dt><dd>{{ $order->created_at?->format('d M Y, H:i:s') ?? 'Not recorded' }}</dd></div>
                        <div class="record-field"><dt>Last updated</dt><dd>{{ $order->updated_at?->format('d M Y, H:i:s') ?? 'Not recorded' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="record-card h-100">
                <h2 class="record-card-header mb-0"><i class="bi bi-person-vcard text-primary"></i>Customer Information</h2>
                <div class="record-card-body">
                    <dl class="record-fields">
                        <div class="record-field"><dt>Full name</dt><dd>{{ $order->customer_name ?: 'Not recorded' }}</dd></div>
                        <div class="record-field"><dt>Phone number</dt><dd>{{ $order->phone ?: 'Not recorded' }}</dd></div>
                        <div class="record-field" style="grid-column: 1 / -1"><dt>Delivery address</dt><dd>{{ $order->delivery_address ?: 'Not recorded' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="record-card">
                <h2 class="record-card-header mb-0"><i class="bi bi-box-seam text-primary"></i>Product Information</h2>
                <div class="table-responsive">
                    <table class="table record-table mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Quantity</th>
                                <th class="text-end">Unit Price (RWF)</th>
                                <th class="text-end">Subtotal (RWF)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->items as $item)
                                @php
                                    $variation = trim(implode(' · ', array_filter([$item->variation_label, $item->variation_value])));
                                    $thumbnail = $imageUrl($item->product?->thumbnail);
                                @endphp
                                <tr>
                                    <td>
                                        <div class="record-product">
                                            <span class="record-product-media" aria-hidden="true">
                                                <span class="record-product-placeholder"><i class="bi bi-image"></i></span>
                                                @if($thumbnail)
                                                    <img class="record-product-image" src="{{ $thumbnail }}" alt="" onerror="this.style.display='none'">
                                                @endif
                                            </span>
                                            <span class="fw-semibold">{{ $item->product_name ?: 'Product name unavailable' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $variation !== '' ? $variation : 'Not recorded' }}</td>
                                    <td>{{ number_format((int) $item->quantity) }}</td>
                                    <td class="text-end text-nowrap">{{ number_format((float) $item->unit_price, 2, '.', ',') }}</td>
                                    <td class="text-end text-nowrap">{{ number_format((float) $item->line_total, 2, '.', ',') }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5" class="small text-muted">
                                        Allocation: {{ $item->vendor?->business_name ?? ($item->assignment_status === 'admin_handled' ? 'Admin / Tresca Hub' : ($item->assignment_status === 'assigned' || $item->assignment_status === 'accepted' ? 'Vendor record unavailable' : 'Unassigned')) }}
                                        · Assignment status: {{ \Illuminate\Support\Str::headline($item->assignment_status ?: 'Not recorded') }}
                                        @php($itemApprovals = $acceptedLogs->where('auditable_id', $item->id))
                                        @if($itemApprovals->isNotEmpty())
                                            · Vendor approval: {{ $itemApprovals->last()->created_at?->format('d M Y, H:i:s') ?? 'Date unavailable' }}
                                        @elseif($approvalHistory)
                                            · Order approved: {{ $approvalHistory->created_at?->format('d M Y, H:i:s') ?? 'Date unavailable' }}
                                        @endif
                                        @php($itemDeclines = $declinedLogs->where('auditable_id', $item->id))
                                        @if($itemDeclines->isNotEmpty())
                                            · Vendor decline recorded: {{ $itemDeclines->last()->created_at?->format('d M Y, H:i:s') ?? 'Date unavailable' }}
                                        @endif
                                        @if($item->internal_notes)
                                            <span class="d-block mt-1">Vendor / processing note: {{ $item->internal_notes }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">No product records are available for this order.</td></tr>
                            @endforelse
                            <tr class="record-total-row">
                                <td colspan="4" class="text-end">Order item subtotal</td>
                                <td class="text-end text-nowrap">{{ number_format((float) $order->subtotal, 2, '.', ',') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="record-card h-100">
                <h2 class="record-card-header mb-0"><i class="bi bi-calculator text-primary"></i>Financial Information</h2>
                <div class="record-card-body">
                    <dl class="record-fields">
                        <div class="record-field"><dt>Order subtotal</dt><dd>RWF {{ number_format((float) $order->subtotal, 2, '.', ',') }}</dd></div>
                        <div class="record-field"><dt>Delivery fee</dt><dd>Not recorded in order data</dd></div>
                        <div class="record-field"><dt>Order total</dt><dd class="fw-bold">RWF {{ number_format((float) $order->total, 2, '.', ',') }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>
        <div class="col-12 col-xl-6">
            <section class="record-card h-100">
                <h2 class="record-card-header mb-0"><i class="bi bi-check2-square text-primary"></i>Approval and Rejection</h2>
                <div class="record-card-body">
                    <dl class="record-fields">
                        <div class="record-field">
                            <dt>Order approval</dt>
                            <dd>{{ $approvalHistory ? 'Approved / confirmed · '.$approvalHistory->created_at?->format('d M Y, H:i:s') : ($acceptedLogs->isNotEmpty() ? 'Vendor approval recorded · '.$acceptedLogs->last()->created_at?->format('d M Y, H:i:s') : 'No approval record available') }}</dd>
                        </div>
                        <div class="record-field">
                            <dt>Rejection reason</dt>
                            <dd>{{ $order->cancellation_reason ?: ($rejectionHistory?->note ?: 'No rejection reason recorded') }}</dd>
                        </div>
                        <div class="record-field">
                            <dt>Rejection date</dt>
                            <dd>{{ $rejectionHistory?->created_at?->format('d M Y, H:i:s') ?? ($declinedLogs->isNotEmpty() ? $declinedLogs->last()->created_at?->format('d M Y, H:i:s') : 'Not recorded') }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="record-card">
                <h2 class="record-card-header mb-0"><i class="bi bi-truck text-primary"></i>Delivery Information</h2>
                @if($order->deliveries->isEmpty())
                    <div class="record-card-body text-muted small">No delivery records available.</div>
                @else
                    <div class="table-responsive">
                        <table class="table record-table mb-0">
                            <thead><tr><th>Delivery Reference</th><th>Status</th><th>Vendor</th><th>Assigned To</th><th>Dispatched</th><th>Delivered</th><th>Customer Confirmation</th></tr></thead>
                            <tbody>
                                @foreach($order->deliveries as $delivery)
                                    @php($confirmation = $customerConfirmationLogs->first(fn ($log) => (int) $log->auditable_id === (int) $delivery->id))
                                    <tr>
                                        <td class="fw-semibold">{{ $delivery->reference }}</td>
                                        <td><span class="record-status {{ $delivery->status === 'delivered' ? 'positive' : ($delivery->status === 'in_transit' ? 'warning' : '') }}">{{ \Illuminate\Support\Str::headline($delivery->status) }}</span></td>
                                        <td>{{ $delivery->vendor?->business_name ?? 'Not recorded' }}</td>
                                        <td>{{ $delivery->officer?->name ?? $delivery->assignedOfficer?->name ?? 'Not recorded' }}</td>
                                        <td>{{ $delivery->dispatched_at?->format('d M Y, H:i:s') ?? 'Not recorded' }}</td>
                                        <td>{{ $delivery->delivered_at?->format('d M Y, H:i:s') ?? 'Not recorded' }}</td>
                                        <td>{{ $confirmation?->created_at?->format('d M Y, H:i:s') ?? 'Not recorded' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>

        <div class="col-12">
            <section class="record-card">
                <h2 class="record-card-header mb-0"><i class="bi bi-clock-history text-primary"></i>Order Timeline</h2>
                <div class="record-card-body">
                    @if($timeline->isEmpty())
                        <p class="text-muted small mb-0">No historical event timestamps are available.</p>
                    @else
                        <div class="record-timeline">
                            @foreach($timeline as $event)
                                <div class="record-event">
                                    <span class="record-event-marker"><i class="bi bi-circle-fill" style="font-size:8px"></i></span>
                                    <div>
                                        <div class="record-event-title">{{ $event['title'] }}</div>
                                        <div class="record-event-time">{{ $event['at']->format('d M Y, H:i:s') }}</div>
                                        @if($event['detail'] !== '')
                                            <div class="record-event-detail">{{ $event['detail'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
