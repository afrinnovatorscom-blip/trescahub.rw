@extends('front.layouts.admin')
@section('page-title', 'Orders History')

@push('styles')
<style>
    .orders-history-page { max-width: 1600px; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .orders-history-page .text-muted { color: #64748b !important; }
    .history-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
    .history-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
    .history-tabs a { flex: 0 0 auto; padding: 9px 13px; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; background: #fff; }
    .history-tabs a.active { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
    .history-summary { padding: 16px 18px; }
    .history-summary-label { color: #64748b; font-size: 13px; font-weight: 600; }
    .history-summary-value { margin-top: 5px; font-size: 23px; font-weight: 700; }
    .history-filters { padding: 16px; }
    .history-export-button { min-height: 42px; padding: 9px 15px; border: 1px solid #1d4ed8; border-radius: 8px; background: #2563eb; color: #fff; font-size: 13px; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(15, 23, 42, .08); transition: background-color .15s ease, box-shadow .15s ease; }
    .history-export-button:hover { color: #fff; background: #1d4ed8; box-shadow: 0 3px 8px rgba(37, 99, 235, .2); }
    .history-export-button:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }
    .orders-history-table { min-width: 1160px; }
    .orders-history-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; white-space: nowrap; border-bottom: 1px solid #e2e8f0; padding: 12px; }
    .orders-history-table td { color: #334155; font-size: 13px; padding: 12px; border-color: #e2e8f0; vertical-align: middle; }
    .orders-history-table tbody tr:hover { background: #f8fafc; }
    .history-status { display: inline-flex; align-items: center; padding: 4px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .history-status--positive { color: #166534; background: #dcfce7; }
    .history-status--warning { color: #92400e; background: #fef3c7; }
    .history-status--danger { color: #991b1b; background: #fee2e2; }
    .history-status--neutral { color: #475569; background: #f1f5f9; }
    .order-products { max-width: 220px; }
    .orders-history-pagination nav { display: flex; justify-content: flex-end; }
    .orders-history-pagination .pagination { gap: 4px; margin: 0; font-size: 13px; }
    .orders-history-pagination .page-link { min-width: 36px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; color: #475569; text-align: center; }
    .orders-history-pagination .page-link:hover { color: #1d4ed8; background: #f8fafc; border-color: #cbd5e1; }
    .orders-history-pagination .page-item.active .page-link { color: #fff; background: #2563eb; border-color: #2563eb; }
    .orders-history-pagination .page-item.disabled .page-link { color: #94a3b8; background: #fff; border-color: #e2e8f0; }
    .orders-history-pagination .page-link svg { width: 14px; height: 14px; }
    @media (max-width: 767.98px) { .orders-history-page .page-heading { align-items: flex-start !important; } .history-summary-value { font-size: 20px; } }
</style>
@endpush

@section('content')
@php
    $statusClass = fn ($status) => match ($status) {
        'confirmed', 'processing', 'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'delivered', 'completed' => 'history-status--positive',
        'pending_confirmation', 'awaiting_stock', 'cancellation_requested' => 'history-status--warning',
        'cancelled', 'returned' => 'history-status--danger',
        default => 'history-status--neutral',
    };
@endphp
<div class="container-fluid py-3 orders-history-page">
    <header class="page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Marketplace / Reports / Orders History</div>
            <h1 class="h3 fw-bold mb-1">Orders History</h1>
            <p class="text-muted small mb-0">Read-only historical records of customer orders.</p>
        </div>
        <a href="{{ route('marketplace.reports.orders-history.export', array_filter($filters, fn ($value) => $value !== null && $value !== '')) }}"
           class="history-export-button d-inline-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-pdf-fill" aria-hidden="true"></i>
            <span>Export PDF</span>
        </a>
    </header>

    <nav class="history-tabs mb-3" aria-label="Report sections">
        <a href="{{ route('marketplace.reports.index', ['category' => 'all']) }}">All Sections</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'products']) }}">Products &amp; Sales</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'services']) }}">Service Requests</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'orders']) }}">Orders &amp; Deliveries</a>
        <a href="{{ route('marketplace.reports.orders-history') }}" class="active" aria-current="page">Orders History</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'inventory']) }}">Inventory</a>
        @if(auth()->user()->hasPermission('vendors.view'))
            <a href="{{ route('marketplace.reports.index', ['category' => 'vendors']) }}">Vendor Fulfillment</a>
        @endif
        @if(auth()->user()->hasPermission('finance.view'))
            <a href="{{ route('marketplace.reports.index', ['category' => 'finance']) }}">Finance</a>
        @endif
    </nav>

    <form method="GET" action="{{ route('marketplace.reports.orders-history') }}" class="history-card history-filters mb-3">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-2">
                <label for="history-customer" class="form-label small fw-semibold">Customer name</label>
                <input id="history-customer" name="customer" type="search" class="form-control" value="{{ $filters['customer'] ?? '' }}" placeholder="Search customer">
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="history-phone" class="form-label small fw-semibold">Phone number</label>
                <input id="history-phone" name="phone" type="search" class="form-control" value="{{ $filters['phone'] ?? '' }}" placeholder="Search phone">
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="history-reference" class="form-label small fw-semibold">Order reference</label>
                <input id="history-reference" name="reference" type="search" class="form-control" value="{{ $filters['reference'] ?? '' }}" placeholder="Search reference">
            </div>
            <div class="col-6 col-md-3 col-xl-1">
                <label for="history-from" class="form-label small fw-semibold">Date from</label>
                <input id="history-from" name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-xl-1">
                <label for="history-to" class="form-label small fw-semibold">Date to</label>
                <input id="history-to" name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-12 col-md-6 col-xl-2">
                <label for="history-status" class="form-label small fw-semibold">Order status</label>
                <select id="history-status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('marketplace.reports.orders-history') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <section class="history-card overflow-hidden">
        <div class="table-responsive">
            <table class="table orders-history-table mb-0">
                <thead>
                    <tr>
                        <th>Order Reference</th>
                        <th>Order Date</th>
                        <th>Customer Name</th>
                        <th>Phone Number</th>
                        <th>Delivery Address</th>
                        <th>Products</th>
                        <th class="text-end">Order Total (RWF)</th>
                        <th>Current Status</th>
                        <th>Last Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $order->reference }}</td>
                            <td class="text-nowrap">{{ $order->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td class="text-nowrap">{{ $order->phone ?: 'Not recorded' }}</td>
                            <td>{{ $order->delivery_address ?: 'Not recorded' }}</td>
                            <td class="order-products">
                                @forelse($order->items as $item)
                                    <span class="d-block">{{ $item->product_name }} × {{ $item->quantity }}</span>
                                @empty
                                    <span class="text-muted">No product records</span>
                                @endforelse
                            </td>
                            <td class="text-end text-nowrap">{{ number_format((float) $order->total, 2, '.', ',') }}</td>
                            <td><span class="history-status {{ $statusClass($order->fulfillment_status) }}">{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</span></td>
                            <td class="text-nowrap">{{ $order->updated_at?->format('d M Y, H:i') ?? 'Not recorded' }}</td>
                            <td class="text-end">
                                <a href="{{ route('marketplace.reports.orders-history.show', $order) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                    <i class="bi bi-eye me-1"></i>View Record
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">No orders match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="orders-history-pagination px-3 py-3 border-top">{{ $orders->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>
@endsection
