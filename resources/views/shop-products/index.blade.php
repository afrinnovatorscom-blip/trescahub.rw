@extends('front.layouts.admin')
@section('page-title', 'Shop Products')

@push('styles')
<style>
    .shop-products-page {
        max-width: 1560px;
        margin: 0 auto;
        color: #0f172a;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .shop-products-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }
    .shop-products-title {
        margin: 0;
        color: #0f172a;
        font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: 26px;
        font-weight: 700;
        line-height: 1.2;
    }
    .shop-products-description {
        margin: 5px 0 0;
        color: #64748b;
        font-size: 14px;
    }
    .shop-products-create {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        border-radius: 8px;
        border-color: #2563eb;
        background: #2563eb;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }
    .shop-products-create:hover { border-color: #1d4ed8; background: #1d4ed8; }
    .shop-products-create:focus-visible,
    .shop-products-filter-btn:focus-visible,
    .shop-products-clear:focus-visible,
    .shop-products-actions .btn:focus-visible {
        outline: 3px solid rgba(37, 99, 235, .25);
        outline-offset: 2px;
        box-shadow: none;
    }
    .shop-products-alert { margin-bottom: 14px; border-radius: 8px; }
    .shop-products-filter {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 12px;
        padding: 12px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    .shop-products-filter-field { width: min(100%, 340px); }
    .shop-products-filter label {
        display: block;
        margin-bottom: 5px;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
    }
    .shop-products-filter .form-select {
        min-height: 40px;
        border-color: #cbd5e1;
        border-radius: 8px;
        color: #334155;
        font-size: 14px;
    }
    .shop-products-filter .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
    }
    .shop-products-filter-btn,
    .shop-products-clear {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        padding: 7px 14px;
        font-size: 13px;
        font-weight: 600;
        white-space: nowrap;
    }
    .shop-products-filter-btn { border-color: #2563eb; background: #2563eb; }
    .shop-products-filter-btn:hover { border-color: #1d4ed8; background: #1d4ed8; }
    .shop-products-clear { color: #475569; border-color: #cbd5e1; background: #fff; text-decoration: none; }
    .shop-products-clear:hover { color: #0f172a; border-color: #94a3b8; background: #f8fafc; }
    .shop-products-table-shell {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }
    .shop-products-table-wrap { width: 100%; overflow-x: auto; }
    .shop-products-table {
        width: 100%;
        min-width: 1100px;
        margin: 0;
        table-layout: fixed;
        color: #334155;
        font-size: 13px;
    }
    .shop-products-table thead th {
        height: 44px;
        padding: 9px 10px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.3;
        vertical-align: middle;
    }
    .shop-products-table tbody td {
        height: 68px;
        padding: 9px 10px;
        border-bottom: 1px solid #edf2f7;
        color: #334155;
        line-height: 1.4;
        vertical-align: middle;
        overflow-wrap: anywhere;
    }
    .shop-products-table tbody tr:last-child td { border-bottom: 0; }
    .shop-products-table tbody tr:not(.shop-products-empty-row):hover { background: #f8fafc; }
    .shop-products-table .number-cell,
    .shop-products-table .price-cell,
    .shop-products-table .working-days-cell,
    .shop-products-table .status-cell,
    .shop-products-table .actions-cell { white-space: nowrap; }
    .shop-products-number { color: #64748b; font-variant-numeric: tabular-nums; }
    .shop-products-thumbnail {
        display: block;
        width: 44px;
        height: 44px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f1f5f9;
        object-fit: cover;
    }
    .shop-products-no-thumbnail {
        display: inline-flex;
        width: 44px;
        height: 44px;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        color: #94a3b8;
        font-size: 16px;
    }
    .shop-products-name { color: #0f172a; font-size: 13px; font-weight: 600; }
    .shop-products-price { color: #0f172a; font-variant-numeric: tabular-nums; font-weight: 600; }
    .shop-products-badge {
        display: inline-flex;
        max-width: 100%;
        min-height: 23px;
        align-items: center;
        justify-content: center;
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
        overflow-wrap: anywhere;
    }
    .shop-products-badge-popular { color: #92400e; border-color: #fde68a; background: #fef3c7; }
    .shop-products-badge-regular { color: #64748b; border-color: #e2e8f0; background: #f8fafc; }
    .shop-products-badge-approved,
    .shop-products-badge-published,
    .shop-products-badge-in-stock { color: #166534; border-color: #bbf7d0; background: #dcfce7; }
    .shop-products-badge-pending { color: #92400e; border-color: #fde68a; background: #fef3c7; }
    .shop-products-badge-rejected,
    .shop-products-badge-out-of-stock { color: #991b1b; border-color: #fecaca; background: #fee2e2; }
    .shop-products-badge-unpublished,
    .shop-products-badge-neutral { color: #475569; border-color: #e2e8f0; background: #f1f5f9; }
    .shop-products-table .popular-cell .shop-products-badge,
    .shop-products-table .status-cell .shop-products-badge { white-space: nowrap; }
    .shop-products-approval { display: flex; flex-direction: column; align-items: flex-start; gap: 4px; }
    .shop-products-vendor { color: #475569; font-size: 11px; line-height: 1.3; }
    .shop-products-actions { display: inline-flex; gap: 4px; }
    .shop-products-actions .btn {
        min-height: 30px;
        border-radius: 6px !important;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: 600;
    }
    .shop-products-actions .btn-warning { color: #854d0e; border-color: #fde68a; background: #fef3c7; }
    .shop-products-actions .btn-warning:hover { color: #713f12; border-color: #fcd34d; background: #fde68a; }
    .shop-products-actions .btn-danger { border-color: #fecaca; color: #b91c1c; background: #fff; }
    .shop-products-actions .btn-danger:hover { border-color: #ef4444; color: #fff; background: #dc2626; }
    .shop-products-empty {
        padding: 48px 16px !important;
        color: #64748b !important;
        text-align: center;
    }
    .shop-products-empty p { margin: 0 0 12px; }
    .shop-products-empty .btn { border-radius: 8px; font-weight: 600; }
    .shop-products-pagination { padding: 12px 16px; border-top: 1px solid #e2e8f0; }
    .shop-products-pagination .pagination { justify-content: center; margin: 0; }
    .shop-products-pagination .page-link { color: #475569; border-color: #e2e8f0; }
    .shop-products-pagination .active .page-link { color: #fff; border-color: #2563eb; background: #2563eb; }

    @media (max-width: 991.98px) {
        .shop-products-page { padding-top: 20px !important; }
        .shop-products-table { min-width: 1080px; }
    }
    @media (max-width: 767.98px) {
        .shop-products-header { align-items: stretch; flex-direction: column; gap: 12px; margin-bottom: 14px; }
        .shop-products-title { font-size: 23px; }
        .shop-products-create { width: 100%; }
        .shop-products-filter { align-items: stretch; flex-wrap: wrap; }
        .shop-products-filter-field { width: 100%; }
        .shop-products-filter-btn,
        .shop-products-clear { flex: 1 1 100px; }
        .shop-products-table-shell { overflow: visible; border: 0; background: transparent; }
        .shop-products-table-wrap { overflow: visible; }
        .shop-products-table {
            display: block;
            min-width: 0;
            border: 0;
        }
        .shop-products-table thead {
            position: absolute;
            width: 1px;
            height: 1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            clip-path: inset(50%);
        }
        .shop-products-table tbody { display: block; }
        .shop-products-table tbody tr:not(.shop-products-empty-row) {
            display: block;
            margin-bottom: 10px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }
        .shop-products-table tbody tr:not(.shop-products-empty-row):hover { background: #fff; }
        .shop-products-table tbody td {
            display: grid;
            grid-template-columns: minmax(88px, 30%) minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            height: auto;
            min-height: 42px;
            padding: 8px 12px;
            border-bottom: 1px solid #edf2f7;
            text-align: left !important;
        }
        .shop-products-table tbody td:last-child { border-bottom: 0; }
        .shop-products-table tbody td::before {
            content: attr(data-label);
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
        }
        .shop-products-table .shop-products-thumbnail,
        .shop-products-table .shop-products-no-thumbnail { width: 40px; height: 40px; }
        .shop-products-table .shop-products-approval { align-items: flex-start; }
        .shop-products-table .actions-cell { white-space: normal; }
        .shop-products-actions .btn { min-height: 36px; padding: 6px 12px; }
        .shop-products-empty-row { display: block; }
        .shop-products-table tbody td.shop-products-empty {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
        }
        .shop-products-table tbody td.shop-products-empty::before { content: none; }
        .shop-products-pagination { padding: 12px 4px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    }
    @media (max-width: 400px) {
        .shop-products-table tbody td { grid-template-columns: minmax(76px, 27%) minmax(0, 1fr); gap: 8px; padding-right: 9px; padding-left: 9px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 shop-products-page">
    <div class="shop-products-header">
        <div>
            <h1 class="shop-products-title">Shop Products</h1>
            <p class="shop-products-description">Manage products available across the marketplace.</p>
        </div>
        <a href="{{ route('admin.shop-products.create') }}" class="btn btn-primary shop-products-create">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>New Product</span>
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shop-products-alert" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(auth()->user()->hasPermission('vendors.view'))
        <form method="GET" action="{{ route('admin.shop-products.index') }}" class="shop-products-filter">
            <div class="shop-products-filter-field">
                <label for="vendorFilter">Filter by vendor</label>
                <select id="vendorFilter" name="vendor_id" class="form-select">
                    <option value="">All vendors</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected(request('vendor_id') == $vendor->id)>{{ $vendor->business_name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary shop-products-filter-btn" type="submit">Filter</button>
            @if(request()->filled('vendor_id'))
                <a class="shop-products-clear" href="{{ route('admin.shop-products.index') }}">Clear</a>
            @endif
        </form>
    @endif

    <div class="shop-products-table-shell">
        @if($products->count() > 0)
            <div class="shop-products-table-wrap">
                <table class="table shop-products-table">
                    <colgroup>
                        <col style="width: 3.5%">
                        <col style="width: 5.7%">
                        <col style="width: 14.2%">
                        <col style="width: 9.2%">
                        <col style="width: 9.1%">
                        <col style="width: 9.1%">
                        <col style="width: 10%">
                        <col style="width: 7.1%">
                        <col style="width: 7.2%">
                        <col style="width: 14.1%">
                        <col style="width: 9.8%">
                        <col style="width: 11%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Thumbnail</th>
                            <th scope="col">Product Name</th>
                            <th scope="col">Popular</th>
                            <th scope="col">Category</th>
                            <th scope="col">Subcategory</th>
                            <th scope="col">Vendor</th>
                            <th scope="col" class="text-end">Price (RWF)</th>
                            <th scope="col">Working Days</th>
                            <th scope="col">Approval</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $approvalStatus = $product->approval_status ?? 'pending';
                                $approvalBadgeClass = match ($approvalStatus) {
                                    'approved' => 'shop-products-badge-approved',
                                    'pending', 'pending_approval', 'changes_requested' => 'shop-products-badge-pending',
                                    'rejected' => 'shop-products-badge-rejected',
                                    default => 'shop-products-badge-neutral',
                                };
                                $publicationStatus = $product->publication_status ?? 'unpublished';
                                $publicationBadgeClass = $publicationStatus === 'published'
                                    ? 'shop-products-badge-published'
                                    : ($publicationStatus === 'unpublished' ? 'shop-products-badge-unpublished' : 'shop-products-badge-neutral');
                            @endphp
                            <tr>
                                <td class="number-cell" data-label="#"><span class="shop-products-number">{{ $loop->iteration }}</span></td>
                                <td data-label="Thumbnail">
                                    @if($product->thumbnail)
                                        <img src="{{ asset('assets/' . $product->thumbnail) }}"
                                             alt="{{ $product->product_name }}"
                                             class="shop-products-thumbnail">
                                    @else
                                        <span class="shop-products-no-thumbnail" aria-label="No thumbnail">—</span>
                                    @endif
                                </td>
                                <td data-label="Product Name"><span class="shop-products-name">{{ $product->product_name }}</span></td>
                                <td class="popular-cell" data-label="Popular">
                                    @if($product->is_popular)
                                        <span class="shop-products-badge shop-products-badge-popular"><i class="bi bi-star-fill me-1" aria-hidden="true"></i>Popular</span>
                                    @else
                                        <span class="shop-products-badge shop-products-badge-regular">Regular</span>
                                    @endif
                                </td>
                                <td data-label="Category">{{ $product->category?->name ?? '—' }}</td>
                                <td data-label="Subcategory">{{ $product->subcategory?->name ?? '—' }}</td>
                                <td data-label="Vendor">
                                    @if($product->creator && $product->creator->hasRole('vendor'))
                                        @if(auth()->user()->hasPermission('vendors.view') && $product->creator->vendor)
                                            {{ $product->creator->vendor->business_name }}
                                        @else
                                            —
                                        @endif
                                    @elseif($product->creator)
                                        TRESCA Hub
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="price-cell text-end" data-label="Price (RWF)"><span class="shop-products-price">{{ number_format($product->price, 0, '.', ',') }}</span></td>
                                <td class="working-days-cell" data-label="Working Days">{{ $product->working_days }} day(s)</td>
                                <td data-label="Approval">
                                    <div class="shop-products-approval">
                                        <span class="shop-products-badge {{ $approvalBadgeClass }}">{{ str_replace('_', ' ', $approvalStatus) }}</span>
                                        <span class="shop-products-badge {{ $publicationBadgeClass }}">{{ $publicationStatus }}</span>
                                        @if(auth()->user()->hasPermission('vendors.view') && $product->vendor)
                                            <span class="shop-products-vendor">{{ $product->vendor->business_name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="status-cell" data-label="Status">
                                    @if($product->status === 'in_stock')
                                        <span class="shop-products-badge shop-products-badge-in-stock">In Stock</span>
                                    @else
                                        <span class="shop-products-badge shop-products-badge-out-of-stock">Out of Stock</span>
                                    @endif
                                </td>
                                <td class="actions-cell" data-label="Actions">
                                    <div class="shop-products-actions">
                                        <a href="{{ route('admin.shop-products.edit', $product) }}" class="btn btn-warning">Edit</a>
                                        <form action="{{ route('admin.shop-products.destroy', $product) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger"
                                                    onclick="return confirm('Delete this product?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="shop-products-pagination">
                {{ $products->links() }}
            </div>
        @else
            <div class="shop-products-empty">
                <p>No products found.</p>
                <a href="{{ route('admin.shop-products.create') }}" class="btn btn-primary btn-sm">Create First Product</a>
            </div>
        @endif
    </div>
</div>
@endsection
