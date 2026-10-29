@extends('front.layouts.admin')
@section('page-title', 'Vendor overview')

@push('styles')
<style>
    .vendor-dashboard { color: #1a2035; }
    .vendor-dashboard .dashboard-intro { color: #7a8499; max-width: 680px; }
    .vendor-dashboard .dashboard-section {
        background: var(--card-bg);
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .035);
    }
    .vendor-dashboard .dashboard-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: -.02em;
    }
    .vendor-dashboard .section-link {
        color: #087f6b;
        font-size: .85rem;
        font-weight: 600;
        text-decoration: none;
    }
    .vendor-dashboard .section-link:hover { color: #065f52; text-decoration: underline; }
    .vendor-dashboard .section-link:focus-visible,
    .vendor-dashboard .quick-action:focus-visible,
    .vendor-dashboard .summary-card:focus-visible,
    .vendor-dashboard .dashboard-table a:focus-visible {
        outline: 2px solid #087f6b;
        outline-offset: 3px;
        border-radius: 6px;
    }
    .vendor-dashboard .quick-action {
        align-items: center;
        background: #fff;
        border: 1px solid #dfe5ec;
        border-radius: 9px;
        color: #334155;
        display: inline-flex;
        font-size: .85rem;
        font-weight: 600;
        gap: 8px;
        min-height: 40px;
        padding: 8px 13px;
        text-decoration: none;
        transition: border-color .18s ease, color .18s ease, background .18s ease;
    }
    .vendor-dashboard .quick-action:hover {
        background: #f0fdf9;
        border-color: #9bdacb;
        color: #087f6b;
    }
    .vendor-dashboard .quick-action-primary {
        background: var(--accent);
        border-color: var(--accent);
        color: #0b0f1a;
    }
    .vendor-dashboard .quick-action-primary:hover {
        background: #00c49e;
        border-color: #00c49e;
        color: #0b0f1a;
    }
    .vendor-dashboard .summary-card {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #e4e9ef;
        border-radius: 13px;
        color: inherit;
        display: flex;
        gap: 14px;
        height: 100%;
        justify-content: space-between;
        min-height: 132px;
        padding: 18px;
        text-decoration: none;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }
    .vendor-dashboard a.summary-card:hover {
        border-color: #9bdacb;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .07);
        color: inherit;
        transform: translateY(-2px);
    }
    .vendor-dashboard .summary-card:focus-visible { border-color: #087f6b; }
    .vendor-dashboard .summary-icon {
        align-items: center;
        border-radius: 10px;
        display: inline-flex;
        flex: 0 0 40px;
        font-size: 1.05rem;
        height: 40px;
        justify-content: center;
    }
    .vendor-dashboard .summary-icon-teal { background: #e6f8f3; color: #087f6b; }
    .vendor-dashboard .summary-icon-blue { background: #edf4ff; color: #3567b2; }
    .vendor-dashboard .summary-icon-amber { background: #fff6e5; color: #9a6500; }
    .vendor-dashboard .summary-icon-red { background: #fff0ef; color: #b5473c; }
    .vendor-dashboard .summary-icon-violet { background: #f3efff; color: #7153aa; }
    .vendor-dashboard .summary-value {
        color: #151d2f;
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: -.04em;
        line-height: 1;
    }
    .vendor-dashboard .summary-label {
        color: #647084;
        font-size: .82rem;
        font-weight: 500;
        margin-top: 8px;
    }
    .vendor-dashboard .summary-arrow { color: #9aa4b2; font-size: .9rem; }
    .vendor-dashboard .dashboard-table { margin-bottom: 0; min-width: 580px; }
    .vendor-dashboard .dashboard-table thead th {
        background: #f8fafc;
        border-bottom: 1px solid #e7ebf0;
        color: #778195;
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .045em;
        padding: 10px 14px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .vendor-dashboard .dashboard-table tbody td {
        border-color: #edf0f4;
        color: #475569;
        font-size: .84rem;
        padding: 12px 14px;
        vertical-align: middle;
    }
    .vendor-dashboard .dashboard-table tbody tr:last-child td { border-bottom: 0; }
    .vendor-dashboard .record-link { color: #087f6b; font-weight: 700; text-decoration: none; }
    .vendor-dashboard .record-link:hover { text-decoration: underline; }
    .vendor-dashboard .record-secondary { color: #8a94a5; font-size: .76rem; }
    .vendor-dashboard .status-badge {
        border: 1px solid transparent;
        border-radius: 999px;
        display: inline-flex;
        font-size: .7rem;
        font-weight: 600;
        line-height: 1.2;
        padding: 4px 8px;
        white-space: nowrap;
    }
    .vendor-dashboard .status-neutral { background: #f1f5f9; border-color: #e2e8f0; color: #475569; }
    .vendor-dashboard .status-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
    .vendor-dashboard .status-info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
    .vendor-dashboard .status-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .vendor-dashboard .status-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .vendor-dashboard .empty-state {
        color: #7a8499;
        padding: 28px 16px;
        text-align: center;
    }
    .vendor-dashboard .empty-state i {
        color: #9aa4b2;
        display: block;
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    .vendor-dashboard .action-item {
        align-items: center;
        border: 1px solid #e8edf2;
        border-radius: 10px;
        color: #334155;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 12px 14px;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease;
    }
    .vendor-dashboard .action-item:hover { background: #f8fbfa; border-color: #b7dfd5; color: #087f6b; }
    .vendor-dashboard .action-item-icon {
        align-items: center;
        background: #fff6e5;
        border-radius: 8px;
        color: #9a6500;
        display: inline-flex;
        flex: 0 0 34px;
        height: 34px;
        justify-content: center;
    }
    @media (max-width: 575.98px) {
        .vendor-dashboard .dashboard-header { align-items: flex-start !important; }
        .vendor-dashboard .quick-actions { width: 100%; }
        .vendor-dashboard .quick-action { flex: 1 1 auto; justify-content: center; }
        .vendor-dashboard .summary-card { min-height: 118px; padding: 15px; }
        .vendor-dashboard .summary-value { font-size: 1.55rem; }
        .vendor-dashboard .section-heading-row { align-items: flex-start !important; }
    }
</style>
@endpush

@section('content')
@php
    $user = auth()->user();
    $statusTone = static fn ($status) => match ($status) {
        'approved', 'completed', 'delivered', 'paid' => 'success',
        'rejected', 'cancelled', 'failed' => 'danger',
        'processing', 'awaiting_stock', 'ready_for_dispatch', 'dispatched', 'out_for_delivery' => 'info',
        'pending_approval', 'pending_confirmation', 'awaiting_vendor_response', 'assigned', 'accepted' => 'warning',
        default => 'neutral',
    };
    $statusLabel = static fn ($status) => \Illuminate\Support\Str::headline((string) $status);
    $summaryCards = [
        [
            'label' => 'Assigned Orders',
            'value' => $orders,
            'icon' => 'bi-bag-check',
            'tone' => 'teal',
            'href' => $user->hasPermission('orders.view_assigned') ? route('vendor.orders.index') : null,
        ],
        [
            'label' => 'Approved Products',
            'value' => $approved,
            'icon' => 'bi-box-seam',
            'tone' => 'blue',
            'href' => $user->hasPermission('products.view') ? route('vendor.products.index') : null,
        ],
        [
            'label' => 'Pending Approval',
            'value' => $pending,
            'icon' => 'bi-hourglass-split',
            'tone' => 'amber',
            'href' => $user->hasPermission('products.view') ? route('vendor.products.index') : null,
        ],
        [
            'label' => 'Orders to Fulfill',
            'value' => $ordersToFulfill,
            'icon' => 'bi-truck',
            'tone' => 'violet',
            'href' => $user->hasPermission('orders.view_assigned') ? route('vendor.orders.index', ['tab' => 'paid']) : null,
        ],
        [
            'label' => 'Rejected Products',
            'value' => $rejected,
            'icon' => 'bi-exclamation-circle',
            'tone' => 'red',
            'href' => $user->hasPermission('products.view') ? route('vendor.products.index') : null,
        ],
        [
            'label' => 'Completed Orders',
            'value' => $completedOrders,
            'icon' => 'bi-check2-circle',
            'tone' => 'teal',
            'href' => null,
        ],
    ];
    $actionItems = [];
    if ($rejected > 0 && $user->hasPermission('products.view')) {
        $actionItems[] = [
            'icon' => 'bi-exclamation-circle',
            'label' => 'Review rejected product submissions',
            'detail' => number_format($rejected).' '.\Illuminate\Support\Str::plural('product', $rejected).' need attention.',
            'href' => route('vendor.products.index'),
        ];
    }
    if ($requests > 0 && $user->hasPermission('requests.view_assigned')) {
        $actionItems[] = [
            'icon' => 'bi-chat-dots',
            'label' => 'Respond to assigned product requests',
            'detail' => number_format($requests).' '.\Illuminate\Support\Str::plural('request', $requests).' awaiting your response.',
            'href' => route('vendor.requests.index'),
        ];
    }
    if ($ordersToFulfill > 0 && $user->hasPermission('orders.view_assigned')) {
        $actionItems[] = [
            'icon' => 'bi-truck',
            'label' => 'Prepare paid orders for delivery',
            'detail' => number_format($ordersToFulfill).' '.\Illuminate\Support\Str::plural('order', $ordersToFulfill).' ready for vendor fulfillment.',
            'href' => route('vendor.orders.index', ['tab' => 'paid']),
        ];
    }
@endphp
<div class="container-fluid py-4 vendor-dashboard">
    @include('marketplace.partials.flash')

    <div class="dashboard-header d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Vendor Dashboard</h1>
            <p class="dashboard-intro mb-0">A clear view of your product submissions, assigned orders, and next actions.</p>
        </div>
        <nav class="quick-actions d-flex flex-wrap gap-2" aria-label="Vendor quick actions">
            @if($user->hasPermission('products.submit'))
                <a class="quick-action quick-action-primary" href="{{ route('vendor.products.create') }}">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Add Product
                </a>
            @endif
            @if($user->hasPermission('products.view'))
                <a class="quick-action" href="{{ route('vendor.products.index') }}">
                    <i class="bi bi-box-seam" aria-hidden="true"></i> My Products
                </a>
            @endif
            @if($user->hasPermission('orders.view_assigned'))
                <a class="quick-action" href="{{ route('vendor.orders.index') }}">
                    <i class="bi bi-bag-check" aria-hidden="true"></i> View Orders
                </a>
            @endif
        </nav>
    </div>

    <section class="row g-3 mb-4" aria-label="Vendor summary">
        @foreach($summaryCards as $card)
            <div class="col-12 col-sm-6 col-xl-4">
                @if($card['href'])
                    <a class="summary-card" href="{{ $card['href'] }}">
                @else
                    <div class="summary-card">
                @endif
                    <div class="d-flex gap-3">
                        <span class="summary-icon summary-icon-{{ $card['tone'] }}">
                            <i class="bi {{ $card['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <div>
                            <div class="summary-value">{{ number_format($card['value']) }}</div>
                            <div class="summary-label">{{ $card['label'] }}</div>
                        </div>
                    </div>
                    @if($card['href'])
                        <i class="bi bi-arrow-up-right summary-arrow" aria-hidden="true"></i>
                    @endif
                @if($card['href'])
                    </a>
                @else
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    <div class="row g-3">
        <div class="col-12">
            <section class="dashboard-section p-3 p-lg-4" aria-labelledby="recent-orders-title">
                <div class="section-heading-row d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="dashboard-section-title mb-1" id="recent-orders-title">Recent Assigned Orders</h2>
                        <p class="text-muted small mb-0">The latest orders containing products assigned to your business.</p>
                    </div>
                    @if($user->hasPermission('orders.view_assigned'))
                        <a class="section-link" href="{{ route('vendor.orders.index') }}">View all orders <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
                @if($recentOrders->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table dashboard-table align-middle">
                            <thead><tr><th>Order</th><th>Customer</th><th>Assigned Items</th><th>Date</th><th>Fulfillment</th><th>Payment</th></tr></thead>
                            <tbody>
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td>
                                            @if($user->hasPermission('orders.view_assigned'))
                                                <a class="record-link" href="{{ route('vendor.orders.show', $order->reference) }}">{{ $order->reference }}</a>
                                            @else
                                                <span class="fw-semibold">{{ $order->reference }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->items->count() }}</td>
                                        <td><span class="text-nowrap">{{ $order->created_at?->format('M j, Y') }}</span></td>
                                        <td><span class="status-badge status-{{ $statusTone($order->fulfillment_status) }}">{{ $statusLabel($order->fulfillment_status) }}</span></td>
                                        <td><span class="status-badge status-{{ $statusTone($order->payment_status) }}">{{ $statusLabel($order->payment_status) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-bag" aria-hidden="true"></i>
                        No assigned orders yet. New assignments will appear here.
                    </div>
                @endif
            </section>
        </div>

        <div class="col-12 col-xl-7">
            <section class="dashboard-section p-3 p-lg-4 h-100" aria-labelledby="recent-products-title">
                <div class="section-heading-row d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h2 class="dashboard-section-title mb-1" id="recent-products-title">Recent Product Submissions</h2>
                        <p class="text-muted small mb-0">Track the latest products submitted by your business.</p>
                    </div>
                    @if($user->hasPermission('products.view'))
                        <a class="section-link" href="{{ route('vendor.products.index') }}">View all products <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
                @if($recentProducts->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table dashboard-table align-middle">
                            <thead><tr><th>Product</th><th>Submitted</th><th>Status</th></tr></thead>
                            <tbody>
                                @foreach($recentProducts as $product)
                                    @php
                                        $productStatus = $product->revision_status === 'pending_approval'
                                            ? 'pending_approval'
                                            : $product->approval_status;
                                        $productStatusLabel = $product->revision_status === 'pending_approval'
                                            ? 'Changes Under Review'
                                            : $statusLabel($productStatus);
                                    @endphp
                                    <tr>
                                        <td>
                                            @if($user->hasPermission('products.view'))
                                                <a class="record-link" href="{{ route('vendor.products.edit', $product->slug) }}">{{ $product->product_name }}</a>
                                            @else
                                                <span class="fw-semibold">{{ $product->product_name }}</span>
                                            @endif
                                        </td>
                                        <td><span class="text-nowrap">{{ ($product->submitted_at ?? $product->created_at)?->format('M j, Y') }}</span></td>
                                        <td><span class="status-badge status-{{ $statusTone($productStatus) }}">{{ $productStatusLabel }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        No product submissions yet. Start by adding your first product.
                        @if($user->hasPermission('products.submit'))
                            <div class="mt-3"><a class="section-link" href="{{ route('vendor.products.create') }}">Add a product <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                        @endif
                    </div>
                @endif
            </section>
        </div>

        @if($actionItems)
            <div class="col-12 col-xl-5">
                <section class="dashboard-section p-3 p-lg-4 h-100" aria-labelledby="action-required-title">
                    <div class="mb-3">
                        <h2 class="dashboard-section-title mb-1" id="action-required-title">Action Required</h2>
                        <p class="text-muted small mb-0">Items that may need your attention.</p>
                    </div>
                    <div class="d-grid gap-2">
                        @foreach($actionItems as $item)
                            <a class="action-item" href="{{ $item['href'] }}">
                                <span class="d-flex align-items-center gap-3">
                                    <span class="action-item-icon"><i class="bi {{ $item['icon'] }}" aria-hidden="true"></i></span>
                                    <span>
                                        <span class="d-block fw-semibold">{{ $item['label'] }}</span>
                                        <span class="record-secondary">{{ $item['detail'] }}</span>
                                    </span>
                                </span>
                                <i class="bi bi-arrow-right flex-shrink-0" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        @endif
    </div>
</div>
@endsection
