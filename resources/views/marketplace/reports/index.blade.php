@extends('front.layouts.admin')
@section('page-title', 'Reports & Analytics')

@push('styles')
<style>
    .reports-page { color: #0f172a; max-width: 1600px; }
    .reports-page .text-muted { color: #64748b !important; }
    
    /* ── Containers & Cards ── */
    .report-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .report-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    }
    .report-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .report-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .report-card-body {
        padding: 20px;
    }

    /* ── Metric Summary Tiles ── */
    .metric-tile {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }
    .metric-tile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        width: 4px;
        background: #cbd5e1;
    }
    .metric-tile.tone-primary::before { background: #2563eb; }
    .metric-tile.tone-success::before { background: #16a34a; }
    .metric-tile.tone-warning::before { background: #d97706; }
    .metric-tile.tone-danger::before { background: #dc2626; }
    .metric-tile.tone-info::before { background: #0284c7; }
    .metric-tile.tone-teal::before { background: #0d9488; }

    .metric-label {
        font-size: 0.76rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .metric-value {
        font-family: 'Syne', sans-serif;
        font-size: 1.55rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .metric-subtext {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 6px;
    }

    /* ── Filter Toolbar ── */
    .filter-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 24px;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        align-items: end;
    }
    .filter-grid .form-control,
    .filter-grid .form-select,
    .filter-grid .btn {
        min-height: 40px;
        font-size: 0.85rem;
    }

    /* ── Category Navigation Tabs ── */
    .report-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 6px;
        margin-bottom: 24px;
        scrollbar-width: thin;
    }
    .report-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        color: #475569;
        font-size: 0.84rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.15s ease;
    }
    .report-tab-btn:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }
    .report-tab-btn.active {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    /* ── Tables ── */
    .report-table {
        margin-bottom: 0;
        width: 100%;
        font-size: 0.85rem;
    }
    .report-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 14px;
        white-space: nowrap;
    }
    .report-table tbody td {
        padding: 12px 14px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    .report-table tbody tr:hover td {
        background: #f8fafc;
    }

    /* ── Status Badges ── */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .status-pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .status-pill-danger  { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .status-pill-info    { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .status-pill-neutral { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* ── Progress Indicators ── */
    .mini-progress-bar {
        height: 6px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
        min-width: 60px;
    }
    .mini-progress-fill {
        height: 100%;
        background: #2563eb;
        border-radius: 999px;
    }
    .mini-progress-fill.tone-success { background: #16a34a; }
    .mini-progress-fill.tone-warning { background: #d97706; }

    /* ── Notice Callouts ── */
    .report-notice {
        background: #f8fafc;
        border-left: 4px solid #3b82f6;
        border-radius: 0 8px 8px 0;
        padding: 14px 18px;
        font-size: 0.83rem;
        color: #334155;
        margin-top: 16px;
    }
    .report-notice-warning {
        background: #fffbeb;
        border-left-color: #f59e0b;
        color: #92400e;
    }

    /* ── Empty State ── */
    .report-empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
    }
    .report-empty-icon {
        font-size: 2.2rem;
        color: #cbd5e1;
        margin-bottom: 12px;
    }

    @media (max-width: 768px) {
        .metric-value { font-size: 1.35rem; }
        .filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 reports-page">

    {{-- Top Header & Breadcrumbs --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Marketplace / Reports &amp; Analytics</div>
            <h1 class="h3 fw-bold mb-1" style="font-family:'Syne',sans-serif; color:#0f172a;">Reports &amp; Analytics</h1>
            <p class="text-muted small mb-0">Operational performance, sales analytics, inventory tracking, and fulfillment metrics.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm" style="font-size:0.83rem; font-weight:600;">
                <i class="bi bi-calendar-event text-primary me-1"></i> {{ $report['period']['formatted'] }} ({{ $report['period']['days'] }} {{ Str::plural('day', $report['period']['days']) }})
            </span>
            <a href="{{ route('marketplace.reports.export', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => $report['category']]) }}"
               class="btn btn-outline-primary d-inline-flex align-items-center gap-2 shadow-sm"
               title="Download print-ready PDF report">
                <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                <span>Export PDF</span>
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Filter errors:</div>
            <ul class="mb-0 small ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter Toolbar --}}
    <div class="filter-panel shadow-sm">
        <form method="GET" action="{{ route('marketplace.reports.index') }}" id="reportFilterForm">
            <div class="filter-grid">
                <div>
                    <label class="form-label small fw-bold text-muted mb-1" for="fromDate">
                        <i class="bi bi-calendar3 me-1"></i> From Date
                    </label>
                    <input type="date" id="fromDate" name="from" value="{{ $report['period']['from'] }}" class="form-control" required>
                </div>
                <div>
                    <label class="form-label small fw-bold text-muted mb-1" for="toDate">
                        <i class="bi bi-calendar3-fill me-1"></i> To Date
                    </label>
                    <input type="date" id="toDate" name="to" value="{{ $report['period']['to'] }}" class="form-control" required>
                </div>
                <div>
                    <label class="form-label small fw-bold text-muted mb-1" for="reportCategory">
                        <i class="bi bi-tags-fill me-1"></i> Report Section
                    </label>
                    <select id="reportCategory" name="category" class="form-select">
                        <option value="all" @selected($report['category'] === 'all')>All Report Sections</option>
                        <option value="products" @selected($report['category'] === 'products')>Products & Sales</option>
                        <option value="services" @selected($report['category'] === 'services')>Service Requests</option>
                        <option value="orders" @selected($report['category'] === 'orders')>Orders & Deliveries</option>
                        <option value="inventory" @selected($report['category'] === 'inventory')>Inventory & Movements</option>
                        @if($report['permissions']['can_view_vendors'])
                            <option value="vendors" @selected($report['category'] === 'vendors')>Vendor Fulfillment</option>
                        @endif
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-fill d-inline-flex align-items-center justify-content-center gap-1">
                        <i class="bi bi-funnel-fill"></i> Apply Filter
                    </button>
                    <a href="{{ route('marketplace.reports.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Reset date filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Category Pills / Navigation --}}
    <div class="report-tabs">
        <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'all']) }}"
           class="report-tab-btn {{ $report['category'] === 'all' ? 'active' : '' }}">
            <i class="bi bi-grid-fill"></i> All Sections
        </a>
        <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'products']) }}"
           class="report-tab-btn {{ $report['category'] === 'products' ? 'active' : '' }}">
            <i class="bi bi-box-seam"></i> Products & Sales
        </a>
        <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'services']) }}"
           class="report-tab-btn {{ $report['category'] === 'services' ? 'active' : '' }}">
            <i class="bi bi-briefcase"></i> Service Requests
        </a>
        <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'orders']) }}"
           class="report-tab-btn {{ $report['category'] === 'orders' ? 'active' : '' }}">
            <i class="bi bi-truck"></i> Orders & Deliveries
        </a>
        <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'inventory']) }}"
           class="report-tab-btn {{ $report['category'] === 'inventory' ? 'active' : '' }}">
            <i class="bi bi-boxes"></i> Inventory
        </a>
        @if($report['permissions']['can_view_vendors'])
            <a href="{{ route('marketplace.reports.index', ['from' => $report['period']['from'], 'to' => $report['period']['to'], 'category' => 'vendors']) }}"
               class="report-tab-btn {{ $report['category'] === 'vendors' ? 'active' : '' }}">
                <i class="bi bi-shop"></i> Vendor Fulfillment
            </a>
        @endif
    </div>

    {{-- SECTION 1: EXECUTIVE SUMMARY --}}
    <section class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h5 fw-bold mb-0 text-dark">
                <i class="bi bi-speedometer2 text-primary me-2"></i>Executive Summary
            </h2>
            <span class="text-muted small">Metrics for {{ $report['period']['formatted'] }}</span>
        </div>

        <div class="row g-3">
            {{-- Total Orders --}}
            <div class="col-6 col-lg-3">
                <div class="metric-tile tone-primary shadow-sm">
                    <div class="metric-label">
                        <span>Total Product Orders</span>
                        <i class="bi bi-receipt text-primary"></i>
                    </div>
                    <div class="metric-value">{{ number_format($report['summary']['orders_total']) }}</div>
                    <div class="metric-subtext">
                        <span class="text-success fw-semibold">{{ $report['summary']['orders_active'] }}</span> non-cancelled
                    </div>
                </div>
            </div>

            {{-- Units Sold --}}
            <div class="col-6 col-lg-3">
                <div class="metric-tile tone-teal shadow-sm">
                    <div class="metric-label">
                        <span>Units Sold</span>
                        <i class="bi bi-box2-fill text-teal"></i>
                    </div>
                    <div class="metric-value">{{ number_format($report['summary']['units_sold']) }}</div>
                    <div class="metric-subtext">Across active customer orders</div>
                </div>
            </div>

            {{-- Gross Order Value --}}
            <div class="col-6 col-lg-3">
                <div class="metric-tile tone-info shadow-sm">
                    <div class="metric-label">
                        <span>Total Order Value</span>
                        <i class="bi bi-cash-stack text-info"></i>
                    </div>
                    <div class="metric-value">{{ number_format($report['summary']['gross_order_value'], 0, '.', ',') }} <span style="font-size:0.78rem; font-weight:600; color:#64748b;">RWF</span></div>
                    <div class="metric-subtext">Cumulative booked demand</div>
                </div>
            </div>

            {{-- Confirmed Revenue --}}
            <div class="col-6 col-lg-3">
                <div class="metric-tile tone-success shadow-sm">
                    <div class="metric-label">
                        <span>Collected Payments</span>
                        <i class="bi bi-shield-check text-success"></i>
                    </div>
                    @if($report['permissions']['can_view_finance'])
                        <div class="metric-value text-success">{{ number_format($report['summary']['collected_revenue'], 0, '.', ',') }} <span style="font-size:0.78rem; font-weight:600; color:#64748b;">RWF</span></div>
                        <div class="metric-subtext">Verified financial collections</div>
                    @else
                        <div class="metric-value text-muted" style="font-size:1.1rem;">Restricted</div>
                        <div class="metric-subtext">Requires finance permissions</div>
                    @endif
                </div>
            </div>

            {{-- Pipeline Breakdown row --}}
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-warning shadow-sm">
                    <div class="metric-label"><span>Awaiting Confirmation</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['orders_awaiting_confirmation'] }}</div>
                    <div class="metric-subtext">Needs admin review</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-info shadow-sm">
                    <div class="metric-label"><span>Processing</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['orders_processing'] }}</div>
                    <div class="metric-subtext">Confirmed or preparing</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-teal shadow-sm">
                    <div class="metric-label"><span>Delivered / Done</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['orders_delivered'] }}</div>
                    <div class="metric-subtext">{{ $report['summary']['orders_completed'] }} marked completed</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-danger shadow-sm">
                    <div class="metric-label"><span>Cancelled</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['orders_cancelled'] }}</div>
                    <div class="metric-subtext">Voided orders</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-primary shadow-sm">
                    <div class="metric-label"><span>Service Requests</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['services_total'] }}</div>
                    <div class="metric-subtext">{{ $report['summary']['services_completed'] }} completed</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-xl-2">
                <div class="metric-tile tone-warning shadow-sm">
                    <div class="metric-label"><span>Low Stock Alerts</span></div>
                    <div class="metric-value" style="font-size:1.3rem;">{{ $report['summary']['low_stock_count'] }}</div>
                    <div class="metric-subtext">{{ $report['summary']['out_of_stock_count'] }} out of stock</div>
                </div>
            </div>
        </div>
    </section>

    {{-- SECTION 2: PRODUCT AND SALES PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'products']))
    <section class="mb-4" id="section-products">
        <div class="report-card shadow-sm">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="bi bi-award-fill text-primary"></i> Product &amp; Sales Performance
                </h3>
                <span class="badge bg-light text-dark border">
                    {{ $report['products']['best_sellers']->count() }} {{ Str::plural('Product', $report['products']['best_sellers']->count()) }} Traded
                </span>
            </div>
            <div class="report-card-body p-0">
                {{-- Best Sellers Table --}}
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Category / Vendor</th>
                                <th class="text-center">Units Ordered</th>
                                <th class="text-center">Units Delivered</th>
                                <th class="text-center">Units Cancelled</th>
                                <th class="text-end">Gross Booked Value</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['products']['best_sellers'] as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item['name'] }}</div>
                                        <div class="small text-muted">SKU: {{ $item['sku'] }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $item['category'] }}</div>
                                        <div class="small text-muted">{{ $item['vendor'] }}</div>
                                    </td>
                                    <td class="text-center fw-bold">{{ number_format($item['units_ordered']) }}</td>
                                    <td class="text-center">
                                        <span class="status-pill status-pill-success">
                                            {{ number_format($item['units_delivered']) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($item['units_cancelled'] > 0)
                                            <span class="status-pill status-pill-danger">{{ number_format($item['units_cancelled']) }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-nowrap">
                                        {{ number_format($item['gross_revenue'], 0, '.', ',') }} RWF
                                    </td>
                                    <td class="text-center">
                                        <span class="status-pill status-pill-neutral text-capitalize">{{ $item['publication_status'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="report-empty-state">
                                            <div class="report-empty-icon"><i class="bi bi-cart-x"></i></div>
                                            <div class="fw-semibold">No product order records found for this period.</div>
                                            <div class="small">Try adjusting the start or end dates in the filter toolbar above.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Order Sources Breakdown & Inquiry Conversion --}}
                <div class="p-3 border-top bg-light">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-7">
                            <div class="fw-semibold small text-muted mb-2 text-uppercase">Orders by Channel & Source</div>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($report['products']['order_sources'] as $src)
                                    <div class="bg-white border rounded px-3 py-2 flex-fill shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small">{{ $src['label'] }}</span>
                                            <span class="badge bg-primary rounded-pill">{{ $src['total_orders'] }} orders</span>
                                        </div>
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span>Value: <strong>{{ number_format($src['gross_value'], 0, '.', ',') }} RWF</strong></span>
                                            <span>Fulfillment: <strong>{{ $src['delivery_rate'] }}%</strong></span>
                                        </div>
                                    </div>
                                @empty
                                    <span class="text-muted small">No order source activity recorded.</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="bg-white border rounded p-3 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold small text-dark"><i class="bi bi-arrow-repeat text-primary me-1"></i> Product Requests Conversion</span>
                                    <span class="badge bg-info text-dark">{{ $report['products']['requests_conversion_rate'] }}% converted</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    {{ $report['products']['requests_converted'] }} out of {{ $report['products']['requests_total'] }} custom product requests were converted into confirmed orders.
                                </div>
                                <div class="small fst-italic text-secondary" style="font-size:0.75rem;">
                                    * Product inquiries without an order reference represent pre-order requests and are not reported as confirmed sales.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>
    @endif

    {{-- SECTION 3: SERVICE REQUEST PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'services']))
    <section class="mb-4" id="section-services">
        <div class="report-card shadow-sm">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="bi bi-briefcase-fill text-primary"></i> Service Request Performance
                </h3>
                <span class="badge bg-light text-dark border">
                    {{ $report['services']['total'] }} Total {{ Str::plural('Request', $report['services']['total']) }}
                </span>
            </div>
            <div class="report-card-body p-0">
                <div class="table-responsive">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Service Category</th>
                                <th class="text-center">Requests Received</th>
                                <th class="text-center">Pending Review</th>
                                <th class="text-center">Reviewed / In-Progress</th>
                                <th class="text-center">Completed</th>
                                <th class="text-end" style="min-width:140px;">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['services']['categories'] as $svc)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $svc['category'] }}</td>
                                    <td class="text-center fw-semibold">{{ $svc['total'] }}</td>
                                    <td class="text-center">
                                        @if($svc['pending'] > 0)
                                            <span class="status-pill status-pill-warning">{{ $svc['pending'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($svc['reviewed'] > 0)
                                            <span class="status-pill status-pill-info">{{ $svc['reviewed'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($svc['completed'] > 0)
                                            <span class="status-pill status-pill-success">{{ $svc['completed'] }}</span>
                                        @else
                                            <span class="text-muted">0</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="mini-progress-bar flex-grow-1">
                                                <div class="mini-progress-fill tone-success" style="width: {{ $svc['completion_rate'] }}%;"></div>
                                            </div>
                                            <span class="fw-semibold small">{{ $svc['completion_rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="report-empty-state">
                                            <div class="report-empty-icon"><i class="bi bi-inbox"></i></div>
                                            <div class="fw-semibold">No service requests recorded during this period.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Disclosure Notice --}}
                <div class="p-3 border-top bg-light">
                    <div class="report-notice m-0">
                        <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                        <strong>Fulfillment Disclosure:</strong> {{ $report['services']['financial_note'] }}
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- SECTION 4: ORDERS AND DELIVERIES PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'orders']))
    <section class="mb-4" id="section-orders">
        <div class="report-card shadow-sm">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="bi bi-truck text-primary"></i> Orders &amp; Delivery Performance
                </h3>
                <div class="d-flex align-items-center gap-2">
                    @if($report['orders']['late_orders'] > 0)
                        <span class="badge bg-danger">
                            <i class="bi bi-exclamation-octagon me-1"></i> {{ $report['orders']['late_orders'] }} Overdue
                        </span>
                    @endif
                    <span class="badge bg-light text-dark border">
                        {{ $report['orders']['orders_placed'] }} Orders in Period
                    </span>
                </div>
            </div>
            <div class="report-card-body">
                {{-- Status Pipeline Grid --}}
                <div class="row g-2 mb-4">
                    @php
                        $statusConfigs = [
                            'pending_confirmation' => ['label' => 'Pending Confirmation', 'class' => 'status-pill-warning'],
                            'confirmed' => ['label' => 'Confirmed', 'class' => 'status-pill-info'],
                            'processing' => ['label' => 'Processing', 'class' => 'status-pill-info'],
                            'awaiting_stock' => ['label' => 'Awaiting Stock', 'class' => 'status-pill-warning'],
                            'ready_for_dispatch' => ['label' => 'Ready for Delivery', 'class' => 'status-pill-info'],
                            'dispatched' => ['label' => 'Dispatched', 'class' => 'status-pill-info'],
                            'out_for_delivery' => ['label' => 'Out for Delivery', 'class' => 'status-pill-info'],
                            'delivered' => ['label' => 'Delivered', 'class' => 'status-pill-success'],
                            'completed' => ['label' => 'Completed', 'class' => 'status-pill-success'],
                            'cancelled' => ['label' => 'Cancelled', 'class' => 'status-pill-danger'],
                        ];
                    @endphp
                    @foreach($statusConfigs as $statusKey => $cfg)
                        <div class="col-6 col-sm-4 col-md-3 col-xl">
                            <div class="border rounded p-2 text-center bg-light">
                                <div class="text-muted small" style="font-size:0.7rem;">{{ $cfg['label'] }}</div>
                                <div class="fw-bold mt-1" style="font-size:1.15rem;">
                                    {{ $report['orders']['by_fulfillment_status'][$statusKey] ?? 0 }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Delivery Metrics Summary --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-bold">Delivery Success Rate</span>
                                <i class="bi bi-check2-circle text-success"></i>
                            </div>
                            <div class="h4 fw-bold mt-1 mb-1">{{ $report['orders']['delivery_success_rate'] }}%</div>
                            <div class="small text-muted">{{ $report['orders']['delivery_tasks_delivered'] }} of {{ $report['orders']['delivery_tasks_total'] }} delivery tasks delivered</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-bold">In-Transit Deliveries</span>
                                <i class="bi bi-bicycle text-info"></i>
                            </div>
                            <div class="h4 fw-bold mt-1 mb-1">{{ $report['orders']['delivery_tasks_in_transit'] }}</div>
                            <div class="small text-muted">Active deliveries assigned to officers</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-white">
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted fw-bold">Average Fulfillment Time</span>
                                <i class="bi bi-clock-history text-primary"></i>
                            </div>
                            <div class="h4 fw-bold mt-1 mb-1">
                                {{ $report['orders']['average_fulfillment_days'] ? $report['orders']['average_fulfillment_days'] . ' days' : 'N/A' }}
                            </div>
                            <div class="small text-muted">
                                {{ $report['orders']['average_fulfillment_days'] ? 'Based on verified delivery timestamps' : 'Pending completed delivery timestamps' }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Detailed Orders Log Table --}}
                <div class="fw-bold small text-dark mb-2 text-uppercase">
                    <i class="bi bi-list-task me-1"></i> Detailed Orders Log (Period Activity)
                </div>
                <div class="table-responsive border rounded">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Date & Customer</th>
                                <th>Source</th>
                                <th>Items</th>
                                <th class="text-end">Total (RWF)</th>
                                <th>Payment</th>
                                <th>Order Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['orders']['recent_orders'] as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('marketplace.orders.show', $order->reference) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $order->reference }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $order->customer_name }}</div>
                                        <div class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }} • {{ $order->phone }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ match($order->source) { 'request_product' => 'Request Product', 'cart_checkout' => 'Cart Checkout', default => 'Storefront' } }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            @foreach($order->items as $item)
                                                <div>{{ $item->quantity }}× {{ $item->product_name }}</div>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold text-nowrap">
                                        {{ number_format($order->total, 0, '.', ',') }}
                                    </td>
                                    <td>
                                        <span class="status-pill status-pill-{{ match($order->payment_status) { 'paid' => 'success', 'partially_paid' => 'warning', default => 'neutral' } }}">
                                            {{ Str::headline($order->payment_status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-pill status-pill-{{ match($order->fulfillment_status) { 'delivered', 'completed' => 'success', 'cancelled' => 'danger', 'confirmed', 'processing', 'ready_for_dispatch' => 'info', default => 'warning' } }}">
                                            {{ $order->fulfillment_status === 'ready_for_dispatch' ? 'Ready for Delivery' : Str::headline($order->fulfillment_status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('marketplace.orders.show', $order->reference) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="View Order">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="report-empty-state">
                                            <div class="report-empty-icon"><i class="bi bi-inbox"></i></div>
                                            <div class="fw-semibold">No orders placed within this date range.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- SECTION 5: INVENTORY AND STOCK MOVEMENT --}}
    @if(in_array($report['category'], ['all', 'inventory']))
    <section class="mb-4" id="section-inventory">
        <div class="report-card shadow-sm">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="bi bi-boxes text-primary"></i> Inventory &amp; Stock Movement Report
                </h3>
                <span class="badge bg-light text-dark border">
                    {{ $report['inventory']['tracked_count'] }} Tracked Products
                </span>
            </div>
            <div class="report-card-body">
                {{-- Inventory Snapshot Tiles --}}
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-white text-center">
                            <div class="small text-muted fw-bold">Stock Added</div>
                            <div class="h4 fw-bold text-success mt-1 mb-0">+{{ number_format($report['inventory']['stock_added']) }}</div>
                            <div class="small text-muted">Period additions & initial stock</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-white text-center">
                            <div class="small text-muted fw-bold">Stock Removed</div>
                            <div class="h4 fw-bold text-danger mt-1 mb-0">-{{ number_format($report['inventory']['stock_removed']) }}</div>
                            <div class="small text-muted">Sales & administrative removals</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-white text-center">
                            <div class="small text-muted fw-bold">Net Adjustments</div>
                            <div class="h4 fw-bold text-primary mt-1 mb-0">{{ $report['inventory']['stock_adjusted'] >= 0 ? '+' : '' }}{{ number_format($report['inventory']['stock_adjusted']) }}</div>
                            <div class="small text-muted">Approved stock corrections</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 bg-white text-center">
                            <div class="small text-muted fw-bold">Total Movement Logs</div>
                            <div class="h4 fw-bold text-dark mt-1 mb-0">{{ number_format($report['inventory']['movements_total']) }}</div>
                            <div class="small text-muted">Approved movement events</div>
                        </div>
                    </div>
                </div>

                @if($report['inventory']['pending_adjustment_requests'] > 0)
                    <div class="report-notice report-notice-warning mb-4">
                        <i class="bi bi-exclamation-circle-fill me-1"></i>
                        <strong>Notice:</strong> There are {{ $report['inventory']['pending_adjustment_requests'] }} pending vendor inventory adjustment requests awaiting administrative review. These requests are excluded from current stock totals until approved.
                    </div>
                @endif

                {{-- Low Stock Alert Table --}}
                <div class="mb-4">
                    <div class="fw-bold small text-dark mb-2 text-uppercase">
                        <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Low Stock & Critical Items
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Category</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-center">Reserved</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Threshold</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['inventory']['low_stock_items'] as $product)
                                    @php $available = $product->stock_quantity - $product->reserved_quantity; @endphp
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $product->product_name }}</td>
                                        <td>{{ $product->sku ?: 'N/A' }}</td>
                                        <td>{{ $product->category?->name ?? 'General' }}</td>
                                        <td class="text-center">{{ $product->stock_quantity }} {{ $product->stock_unit }}</td>
                                        <td class="text-center text-muted">{{ $product->reserved_quantity }}</td>
                                        <td class="text-center fw-bold {{ $available <= 0 ? 'text-danger' : 'text-warning' }}">
                                            {{ $available }}
                                        </td>
                                        <td class="text-center text-muted">{{ $product->low_stock_threshold }}</td>
                                        <td class="text-center">
                                            @if($available <= 0)
                                                <span class="status-pill status-pill-danger">Out of Stock</span>
                                            @else
                                                <span class="status-pill status-pill-warning">Low Stock</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="p-3 text-center text-muted small">
                                                <i class="bi bi-check-circle text-success me-1"></i> All inventory-tracked products are currently above their low-stock thresholds.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Recent Movements Log --}}
                <div>
                    <div class="fw-bold small text-dark mb-2 text-uppercase">
                        <i class="bi bi-clock-history me-1"></i> Recent Inventory Movements in Period
                    </div>
                    <div class="table-responsive border rounded">
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th class="text-center">Delta</th>
                                    <th class="text-center">Stock Before → After</th>
                                    <th>Recorded By</th>
                                    <th>Reason / Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($report['inventory']['recent_movements'] as $movement)
                                    <tr>
                                        <td class="text-nowrap small text-muted">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $movement->product?->product_name ?? 'Unknown Product' }}</div>
                                            <div class="small text-muted">SKU: {{ $movement->product?->sku ?? 'N/A' }}</div>
                                        </td>
                                        <td>
                                            <span class="status-pill status-pill-{{ match($movement->type) { 'addition', 'initial_stock' => 'success', 'removal', 'sale' => 'danger', 'adjustment' => 'info', default => 'neutral' } }}">
                                                {{ Str::headline($movement->type) }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold {{ $movement->quantity_delta >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                                        </td>
                                        <td class="text-center small">
                                            {{ $movement->stock_before }} → <strong>{{ $movement->stock_after }}</strong>
                                        </td>
                                        <td>
                                            <span class="small">{{ $movement->actor?->name ?? 'System' }}</span>
                                        </td>
                                        <td class="small text-muted" style="max-width:240px;">
                                            {{ $movement->note ?: 'No notes recorded' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="report-empty-state">
                                                <div class="report-empty-icon"><i class="bi bi-box-arrow-in-down"></i></div>
                                                <div class="fw-semibold">No stock movements recorded for this date range.</div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </section>
    @endif

    {{-- SECTION 6: VENDOR PERFORMANCE REPORT --}}
    @if(in_array($report['category'], ['all', 'vendors']) && $report['permissions']['can_view_vendors'])
    <section class="mb-4" id="section-vendors">
        <div class="report-card shadow-sm">
            <div class="report-card-header">
                <h3 class="report-card-title">
                    <i class="bi bi-shop text-primary"></i> Vendor Performance &amp; Fulfillment
                </h3>
                <span class="badge bg-light text-dark border">
                    {{ count($report['vendors']['vendors']) }} Active Vendors in Period
                </span>
            </div>
            <div class="report-card-body">
                {{-- In-House vs Vendor Comparison --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-white shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-uppercase text-muted"><i class="bi bi-building text-primary me-1"></i> Admin / In-House Fulfillment</span>
                                <span class="badge bg-primary rounded-pill">{{ $report['vendors']['comparison']['in_house']['items_count'] }} Items</span>
                            </div>
                            <div class="row g-2 text-center mt-2">
                                <div class="col-4">
                                    <div class="small text-muted">Units Sold</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($report['vendors']['comparison']['in_house']['units_count']) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Delivered Units</div>
                                    <div class="h5 fw-bold text-success mb-0">{{ number_format($report['vendors']['comparison']['in_house']['delivered_units']) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Booked Value</div>
                                    <div class="h6 fw-bold mb-0">{{ number_format($report['vendors']['comparison']['in_house']['gross_value'], 0, '.', ',') }} RWF</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-3 bg-white shadow-sm h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold small text-uppercase text-muted"><i class="bi bi-shop-window text-teal me-1"></i> Vendor-Allocated Fulfillment</span>
                                <span class="badge bg-info text-dark rounded-pill">{{ $report['vendors']['comparison']['vendor']['items_count'] }} Items</span>
                            </div>
                            <div class="row g-2 text-center mt-2">
                                <div class="col-4">
                                    <div class="small text-muted">Units Assigned</div>
                                    <div class="h5 fw-bold mb-0">{{ number_format($report['vendors']['comparison']['vendor']['units_count']) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Delivered Units</div>
                                    <div class="h5 fw-bold text-success mb-0">{{ number_format($report['vendors']['comparison']['vendor']['delivered_units']) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="small text-muted">Booked Value</div>
                                    <div class="h6 fw-bold mb-0">{{ number_format($report['vendors']['comparison']['vendor']['gross_value'], 0, '.', ',') }} RWF</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Vendor Detail Table --}}
                <div class="table-responsive border rounded">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Vendor Business Name</th>
                                <th>District</th>
                                <th class="text-center">Items Assigned</th>
                                <th class="text-center">Accepted</th>
                                <th class="text-center">In Progress</th>
                                <th class="text-center">Delivered</th>
                                <th class="text-end">Assigned Value (RWF)</th>
                                <th class="text-end">Fulfillment Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($report['vendors']['vendors'] as $row)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $row['name'] }}</td>
                                    <td>{{ $row['district'] }}</td>
                                    <td class="text-center fw-semibold">{{ $row['assigned'] }}</td>
                                    <td class="text-center">
                                        <span class="status-pill status-pill-info">{{ $row['accepted'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-pill status-pill-warning">{{ $row['in_progress'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-pill status-pill-success">{{ $row['fulfilled'] }}</span>
                                    </td>
                                    <td class="text-end fw-bold text-nowrap">
                                        {{ number_format($row['total_value'], 0, '.', ',') }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <div class="mini-progress-bar flex-grow-1">
                                                <div class="mini-progress-fill tone-success" style="width: {{ $row['fulfillment_rate'] }}%;"></div>
                                            </div>
                                            <span class="fw-semibold small">{{ $row['fulfillment_rate'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="report-empty-state">
                                            <div class="report-empty-icon"><i class="bi bi-shop"></i></div>
                                            <div class="fw-semibold">No vendor-assigned order items found for this period.</div>
                                            <div class="small">Orders handled entirely in-house appear under Admin fulfillment.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>
    @endif

</div>
@endsection
