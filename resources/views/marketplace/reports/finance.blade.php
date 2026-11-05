@extends('front.layouts.admin')
@section('page-title', 'Finance Report')

@push('styles')
<style>
    .finance-page { max-width: 1600px; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .finance-page .text-muted { color: #64748b !important; }
    .finance-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; }
    .finance-filter { padding: 16px; }
    .finance-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; }
    .finance-tabs a { flex: 0 0 auto; padding: 9px 13px; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; background: #fff; }
    .finance-tabs a.active { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
    .finance-summary { padding: 18px 20px; }
    .finance-summary-label { color: #64748b; font-size: 13px; font-weight: 600; }
    .finance-summary-value { color: #0f172a; font-size: 24px; font-weight: 700; margin-top: 6px; }
    .finance-table { min-width: 900px; }
    .finance-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 600; white-space: nowrap; border-bottom: 1px solid #e2e8f0; padding: 12px 14px; }
    .finance-table td { color: #334155; font-size: 13px; padding: 12px 14px; border-color: #e2e8f0; vertical-align: middle; }
    .finance-table tbody tr:hover { background: #f8fafc; }
    .finance-pagination nav { display: flex; justify-content: flex-end; }
    .finance-pagination .pagination { gap: 4px; margin: 0; font-size: 13px; }
    .finance-pagination .page-link { min-width: 36px; padding: 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; color: #475569; text-align: center; }
    .finance-pagination .page-link:hover { color: #1d4ed8; background: #f8fafc; border-color: #cbd5e1; }
    .finance-pagination .page-item.active .page-link { color: #fff; background: #2563eb; border-color: #2563eb; }
    .finance-pagination .page-item.disabled .page-link { color: #94a3b8; background: #fff; border-color: #e2e8f0; }
    .finance-pagination .page-link svg { width: 14px; height: 14px; }
    @media (max-width: 767.98px) { .finance-page .page-heading { align-items: flex-start !important; } .finance-page .finance-summary-value { font-size: 20px; } }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 finance-page">
    <header class="page-heading d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Marketplace / Reports / Finance</div>
            <h1 class="h3 fw-bold mb-1">Finance Report</h1>
            <p class="text-muted small mb-0">Order totals for the selected reporting period.</p>
        </div>
        <a href="{{ route('marketplace.reports.export', array_filter([
            'from' => $financeReport['period']['from'],
            'to' => $financeReport['period']['to'],
            'q' => $financeReport['search'],
            'category' => 'finance',
        ], fn ($value) => $value !== null && $value !== '')) }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-pdf"></i><span>Export PDF</span>
        </a>
    </header>

    <nav class="finance-tabs mb-3" aria-label="Report sections">
        <a href="{{ route('marketplace.reports.index', ['category' => 'all']) }}">All Sections</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'products']) }}">Products &amp; Sales</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'services']) }}">Service Requests</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'orders']) }}">Orders &amp; Deliveries</a>
        <a href="{{ route('marketplace.reports.orders-history') }}">Orders History</a>
        <a href="{{ route('marketplace.reports.index', ['category' => 'inventory']) }}">Inventory</a>
        @if($canViewVendors)
            <a href="{{ route('marketplace.reports.index', ['category' => 'vendors']) }}">Vendor Fulfillment</a>
        @endif
        <a href="{{ route('marketplace.reports.index', ['category' => 'finance']) }}" class="active" aria-current="page">Finance</a>
    </nav>

    <form method="GET" action="{{ route('marketplace.reports.index') }}" class="finance-card finance-filter mb-3">
        <input type="hidden" name="category" value="finance">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="finance-search" class="form-label small fw-semibold">Search orders</label>
                <input id="finance-search" name="q" type="search" class="form-control" value="{{ $financeReport['search'] }}" placeholder="Reference, customer, address or status">
            </div>
            <div class="col-6 col-md-2">
                <label for="finance-from" class="form-label small fw-semibold">From date</label>
                <input id="finance-from" name="from" type="date" class="form-control" value="{{ $financeReport['period']['from'] }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="finance-to" class="form-label small fw-semibold">To date</label>
                <input id="finance-to" name="to" type="date" class="form-control" value="{{ $financeReport['period']['to'] }}">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Apply filters</button>
                <a href="{{ route('marketplace.reports.index', ['category' => 'finance']) }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="small text-muted mb-3">Reporting period: {{ $financeReport['period']['formatted'] }}</div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6">
            <section class="finance-card finance-summary h-100">
                <div class="finance-summary-label">Total Orders</div>
                <div class="finance-summary-value">{{ number_format($financeReport['total_orders']) }}</div>
            </section>
        </div>
        <div class="col-12 col-md-6">
            <section class="finance-card finance-summary h-100">
                <div class="finance-summary-label">Total Order Value</div>
                <div class="finance-summary-value">RWF {{ number_format($financeReport['total_order_value'], 2, '.', ',') }}</div>
            </section>
        </div>
    </div>

    <section class="finance-card overflow-hidden">
        <div class="table-responsive">
            <table class="table finance-table mb-0">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>Order Reference</th>
                        <th>Customer</th>
                        <th>Delivery Address</th>
                        <th class="text-end">Order Amount</th>
                        <th>Order Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($financeReport['orders'] as $order)
                        <tr>
                            <td class="text-nowrap">{{ $order->created_at?->format('d M Y') }}</td>
                            <td class="fw-semibold">{{ $order->reference }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->delivery_address }}</td>
                            <td class="text-end text-nowrap">RWF {{ number_format((float) $order->total, 2, '.', ',') }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No orders match the selected filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($financeReport['orders']->hasPages())
            <div class="finance-pagination px-3 py-3 border-top">{{ $financeReport['orders']->links('pagination::bootstrap-5') }}</div>
        @endif
    </section>
</div>
@endsection
