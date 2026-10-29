@extends('front.layouts.admin')
@section('page-title', 'Review: ' . $product->product_name)

@push('styles')
<style>
    .product-review-container {
        max-width: 1440px;
        margin: 0 auto;
        color: #0f172a;
    }
    .review-breadcrumb {
        font-size: 0.85rem;
    }
    .review-breadcrumb a {
        color: #64748b;
        text-decoration: none;
    }
    .review-breadcrumb a:hover {
        color: #2563eb;
    }
    .review-header-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .review-header-title {
        font-size: 1.55rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        line-height: 1.25;
    }
    .review-header-subtitle {
        color: #64748b;
        font-size: 0.92rem;
        margin-bottom: 0;
    }
    .review-badge-group {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        border-radius: 9999px;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .status-badge-pending {
        background-color: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
    }
    .status-badge-approved {
        background-color: #ecfdf5;
        color: #15803d;
        border: 1px solid #a7f3d0;
    }
    .status-badge-rejected {
        background-color: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .status-badge-changes {
        background-color: #fff7ed;
        color: #c2410c;
        border: 1px solid #fed7aa;
    }
    .status-badge-neutral {
        background-color: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .status-badge-primary {
        background-color: #eff6ff;
        color: #2563eb;
        border: 1px solid #bfdbfe;
    }

    /* 4-Item Submission Summary Strip */
    .review-meta-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .review-meta-item {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .review-meta-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .review-meta-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 2px;
    }
    .review-meta-val {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .review-meta-sub {
        font-size: 0.78rem;
        color: #64748b;
        margin-top: 2px;
    }

    /* Content Cards */
    .review-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 22px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .review-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 14px;
        margin-bottom: 18px;
        border-bottom: 1px solid #f1f5f9;
    }
    .review-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Product Image Gallery */
    .main-preview-box {
        position: relative;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        height: 380px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: pointer;
    }
    .main-preview-box img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.25s ease;
    }
    .main-preview-box:hover img {
        transform: scale(1.02);
    }
    .preview-zoom-badge {
        position: absolute;
        bottom: 12px;
        right: 12px;
        background: rgba(15, 23, 42, 0.75);
        color: #ffffff;
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 6px;
        backdrop-filter: blur(4px);
        pointer-events: none;
    }
    .thumbnail-strip {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 6px 2px 4px;
        margin-top: 14px;
    }
    .review-thumb-btn {
        width: 68px;
        height: 68px;
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        padding: 2px;
        background: #ffffff;
        cursor: pointer;
        overflow: hidden;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s ease;
    }
    .review-thumb-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
    }
    .review-thumb-btn:hover {
        border-color: #93c5fd;
    }
    .review-thumb-btn.active-thumb {
        border-color: #2563eb;
        box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
    }
    .empty-image-placeholder {
        padding: 48px 24px;
        text-align: center;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 10px;
    }

    /* Product Attributes Definition List */
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px 24px;
    }
    .detail-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 3px;
    }
    .detail-value {
        font-size: 0.94rem;
        font-weight: 500;
        color: #0f172a;
    }
    .description-well {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 18px;
        font-size: 0.92rem;
        line-height: 1.6;
        color: #334155;
    }

    /* Stock Metric Cards */
    .stock-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .stock-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        text-align: center;
    }
    .stock-card-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 4px;
    }
    .stock-card-val {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0f172a;
    }

    /* Timeline in Review History */
    .review-history-timeline {
        position: relative;
        padding-left: 20px;
    }
    .review-history-timeline::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 6px;
        width: 2px;
        background: #e2e8f0;
    }
    .review-history-item {
        position: relative;
        margin-bottom: 18px;
    }
    .review-history-item:last-child {
        margin-bottom: 0;
    }
    .review-history-point {
        position: absolute;
        left: -20px;
        top: 5px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #2563eb;
        border: 3px solid #ffffff;
        box-shadow: 0 0 0 1px #cbd5e1;
    }
    .review-history-content {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
    }
    .review-history-note {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-left: 3px solid #2563eb;
    }

    /* Sticky Sidebar on Desktop */
    @media (min-width: 992px) {
        .sticky-decision-column {
            position: sticky;
            top: 84px;
            z-index: 10;
        }
    }
    @media (max-width: 991.98px) {
        .review-meta-strip {
            grid-template-columns: repeat(2, 1fr);
        }
        .stock-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 575.98px) {
        .review-meta-strip {
            grid-template-columns: 1fr;
        }
        .detail-grid {
            grid-template-columns: 1fr;
        }
        .stock-grid {
            grid-template-columns: 1fr;
        }
        .main-preview-box {
            height: 260px;
        }
    }
</style>
@endpush

@section('content')
@php
    $assetUrl = fn ($path) => asset('assets/' . ltrim(str_replace('public/assets/', '', (string) $path), '/'));

    // Consolidate images (including pending ones if in revision)
    $galleryImages = array_values(array_unique(array_filter(array_merge(
        [$product->thumbnail, $product->pending_payload['thumbnail'] ?? null],
        $product->images ?? [],
        $product->pending_payload['images'] ?? []
    ))));

    $hasImages = count($galleryImages) > 0;
    $primaryImageSrc = $hasImages ? $assetUrl($galleryImages[0]) : null;

    // Check variations
    $variations = !empty($product->pending_payload['price_variations']) 
        ? $product->pending_payload['price_variations'] 
        : ($product->variations->isNotEmpty() ? $product->variations : collect([]));

    $hasVariations = count($variations) > 0;

    // Check specifications
    $specs = !empty($product->pending_payload['specifications']) 
        ? $product->pending_payload['specifications'] 
        : ($product->specifications ?? []);
    $filteredSpecs = collect($specs)->filter(fn($s) => !empty($s['label']) || !empty($s['value']))->values();

    // Check features
    $features = !empty($product->pending_payload['features']) 
        ? $product->pending_payload['features'] 
        : ($product->features ?? []);
    $filteredFeatures = collect($features)->filter(fn($f) => !empty(trim((string)$f)))->values();

    $hasSpecsOrFeatures = $filteredSpecs->isNotEmpty() || $filteredFeatures->isNotEmpty();

    // Approval status badge styling
    $statusBadgeClass = match($product->approval_status) {
        'approved' => 'status-badge-approved',
        'rejected' => 'status-badge-rejected',
        'changes_requested' => 'status-badge-changes',
        default => 'status-badge-pending',
    };
    $statusIcon = match($product->approval_status) {
        'approved' => 'bi-check-circle-fill',
        'rejected' => 'bi-x-circle-fill',
        'changes_requested' => 'bi-pencil-square',
        default => 'bi-clock-fill',
    };
@endphp

<div class="container-fluid py-3 product-review-container">

    {{-- Breadcrumb Navigation --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb review-breadcrumb mb-0 py-1">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}"><i class="bi bi-house-door me-1"></i>Admin</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('marketplace.approvals.index') }}">Approvals</a>
            </li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">
                Review Product
            </li>
        </ol>
    </nav>

    {{-- Flash Notifications --}}
    @include('marketplace.partials.flash')

    {{-- Page Header Card --}}
    <div class="review-header-card">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 0.72rem; letter-spacing: 0.08em;">
                        Product Approval Review
                    </span>
                    @if($product->sku)
                        <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.75rem;">
                            SKU: {{ $product->sku }}
                        </span>
                    @endif
                </div>
                <h1 class="review-header-title">{{ $product->product_name }}</h1>
                <p class="review-header-subtitle">
                    Review submitted details, specifications, pricing, and inventory to approve or request changes for this product.
                </p>
            </div>
            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                <div class="review-badge-group">
                    <span class="status-badge {{ $statusBadgeClass }}">
                        <i class="bi {{ $statusIcon }}"></i>
                        {{ ucwords(str_replace('_', ' ', (string) $product->approval_status)) }}
                    </span>
                    <span class="status-badge {{ $product->publication_status === 'published' ? 'status-badge-primary' : 'status-badge-neutral' }}">
                        <i class="bi {{ $product->publication_status === 'published' ? 'bi-globe2' : 'bi-eye-slash' }}"></i>
                        {{ ucfirst((string) $product->publication_status) }}
                    </span>
                    @if($product->revision_status)
                        <span class="status-badge status-badge-changes">
                            <i class="bi bi-arrow-repeat"></i>
                            Revision {{ ucwords(str_replace('_', ' ', (string) $product->revision_status)) }}
                        </span>
                    @endif
                </div>
                <a href="{{ route('marketplace.approvals.index') }}" class="btn btn-outline-secondary btn-sm text-nowrap d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i> Back to Queue
                </a>
            </div>
        </div>
    </div>

    {{-- Pending Customer-Facing Revisions Notice --}}
    @if($product->pending_payload)
        <div class="alert alert-warning d-flex align-items-start gap-3 p-3 mb-4 shadow-sm border-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill text-warning fs-4 flex-shrink-0"></i>
            <div>
                <div class="fw-bold mb-1">Pending Customer-Facing Revisions</div>
                <div>Unreviewed customer-facing changes are waiting. The public listing still shows the last approved version.</div>
            </div>
        </div>
    @endif

    {{-- Submission Information Summary Strip (4 Cards) --}}
    <section class="review-meta-strip" aria-label="Submission Overview">
        <div class="review-meta-item">
            <div class="review-meta-icon">
                <i class="bi bi-shop"></i>
            </div>
            <div class="min-w-0">
                <div class="review-meta-label">Vendor</div>
                <div class="review-meta-val" title="{{ $product->vendor?->business_name ?? 'Not assigned' }}">
                    {{ $product->vendor?->business_name ?? 'Not assigned' }}
                </div>
                <div class="review-meta-sub">
                    By {{ $product->submitter->name ?? 'System' }}
                </div>
            </div>
        </div>

        <div class="review-meta-item">
            <div class="review-meta-icon">
                <i class="bi bi-calendar3"></i>
            </div>
            <div class="min-w-0">
                <div class="review-meta-label">Submitted On</div>
                <div class="review-meta-val">
                    {{ ($product->submitted_at ?? $product->created_at)?->format('M d, Y') ?? '—' }}
                </div>
                <div class="review-meta-sub">
                    {{ ($product->submitted_at ?? $product->created_at)?->format('H:i') ?? '—' }}
                </div>
            </div>
        </div>

        <div class="review-meta-item">
            <div class="review-meta-icon">
                <i class="bi bi-tags"></i>
            </div>
            <div class="min-w-0">
                <div class="review-meta-label">Category</div>
                <div class="review-meta-val" title="{{ $product->category?->name ?? 'Unassigned' }}">
                    {{ $product->category?->name ?? 'Unassigned' }}
                </div>
                <div class="review-meta-sub">
                    {{ $product->subcategory?->name ?? 'Primary category' }}
                </div>
            </div>
        </div>

        <div class="review-meta-item">
            <div class="review-meta-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="min-w-0">
                <div class="review-meta-label">Pricing & Terms</div>
                <div class="review-meta-val">
                    RWF {{ number_format((float) ($product->price ?? 0), 0, '.', ',') }}
                </div>
                <div class="review-meta-sub">
                    {{ $product->working_days }} working days delivery
                </div>
            </div>
        </div>
    </section>

    {{-- Main Two-Column Layout --}}
    <div class="row g-4">

        {{-- Left Column: Product Information & Inspection (8 cols) --}}
        <div class="col-lg-8">

            {{-- 1. Product Images Gallery Card --}}
            <div class="review-card">
                <div class="review-card-header">
                    <h2 class="review-card-title">
                        <i class="bi bi-images text-primary"></i> Submitted Product Images
                    </h2>
                    @if($hasImages)
                        <span class="badge bg-light text-muted border">
                            {{ count($galleryImages) }} {{ count($galleryImages) === 1 ? 'image' : 'images' }}
                        </span>
                    @endif
                </div>

                @if($hasImages)
                    <div class="main-preview-box" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" title="Click to view full image">
                        <img id="reviewMainImage" src="{{ $primaryImageSrc }}" alt="{{ $product->product_name }}">
                        <div class="preview-zoom-badge">
                            <i class="bi bi-arrows-fullscreen me-1"></i> Click to enlarge
                        </div>
                    </div>

                    @if(count($galleryImages) > 1)
                        <div class="thumbnail-strip" role="group" aria-label="Product image gallery thumbnails">
                            @foreach($galleryImages as $index => $img)
                                @php $thumbUrl = $assetUrl($img); @endphp
                                <button type="button" 
                                        class="review-thumb-btn {{ $index === 0 ? 'active-thumb' : '' }}" 
                                        data-img-src="{{ $thumbUrl }}"
                                        aria-label="View product image {{ $index + 1 }}">
                                    <img src="{{ $thumbUrl }}" alt="Thumbnail {{ $index + 1 }}" onerror="this.src='{{ asset('assets/front/images/placeholder.jpg') }}'">
                                </button>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="empty-image-placeholder">
                        <i class="bi bi-card-image text-muted d-block mb-2" style="font-size: 2.8rem;"></i>
                        <h6 class="fw-bold text-dark mb-1">No Product Images Uploaded</h6>
                        <p class="text-muted small mb-0">This submission does not include a thumbnail or gallery images.</p>
                    </div>
                @endif
            </div>

            {{-- 2. Basic Information & Description Card --}}
            <div class="review-card">
                <div class="review-card-header">
                    <h2 class="review-card-title">
                        <i class="bi bi-card-text text-primary"></i> Basic Information & Description
                    </h2>
                    <span class="badge bg-light text-dark border">
                        {{ ucfirst(str_replace('_', ' ', (string) $product->status)) }}
                    </span>
                </div>

                <div class="detail-grid mb-4">
                    <div>
                        <div class="detail-label">Product Name</div>
                        <div class="detail-value fw-bold">{{ $product->product_name }}</div>
                    </div>
                    <div>
                        <div class="detail-label">SKU Reference</div>
                        <div class="detail-value font-monospace">{{ $product->sku ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Category</div>
                        <div class="detail-value">{{ $product->category?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Subcategory</div>
                        <div class="detail-value">{{ $product->subcategory?->name ?? 'None' }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Product Type</div>
                        <div class="detail-value">{{ ucfirst((string) ($product->product_type ?: 'Standard')) }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Condition</div>
                        <div class="detail-value">{{ ucfirst((string) ($product->condition ?: 'Brand New')) }}</div>
                    </div>
                    <div>
                        <div class="detail-label">Fulfillment / Delivery</div>
                        <div class="detail-value">{{ $product->working_days }} working days</div>
                    </div>
                    <div>
                        <div class="detail-label">Listing Status</div>
                        <div class="detail-value">{{ ucfirst(str_replace('_', ' ', (string) $product->status)) }}</div>
                    </div>
                    @if(auth()->user()->hasPermission('finance.view') && $product->acquisition_cost)
                        <div>
                            <div class="detail-label">Acquisition Cost</div>
                            <div class="detail-value text-success fw-bold">
                                RWF {{ number_format((float) $product->acquisition_cost, 0, '.', ',') }}
                            </div>
                        </div>
                    @endif
                    @if(auth()->user()->hasPermission('vendors.view'))
                        <div>
                            <div class="detail-label">Assigned Vendor</div>
                            <div class="detail-value">{{ $product->vendor?->business_name ?? 'Not assigned' }}</div>
                        </div>
                    @endif
                </div>

                <div class="mb-3">
                    <div class="detail-label mb-2">Product Description</div>
                    @if($product->description)
                        <div class="description-well">{!! nl2br(e($product->description)) !!}</div>
                    @else
                        <div class="description-well text-muted fst-italic">No product description was provided with this submission.</div>
                    @endif
                </div>

                @if($product->internal_notes)
                    <div class="alert alert-secondary d-flex align-items-start gap-2 mb-0 py-2 px-3 border" style="font-size: 0.88rem;">
                        <i class="bi bi-lock-fill text-muted mt-1"></i>
                        <div>
                            <strong class="text-dark">Internal Staff Notes:</strong>
                            <span class="text-muted">{{ $product->internal_notes }}</span>
                        </div>
                    </div>
                @endif
            </div>

            {{-- 3. Pricing & Variations Card (Omits variation table if no variations exist) --}}
            <div class="review-card">
                <div class="review-card-header">
                    <h2 class="review-card-title">
                        <i class="bi bi-tag text-primary"></i> Pricing & Variations
                    </h2>
                </div>

                {{-- Base Price Display --}}
                <div class="d-flex flex-wrap align-items-center gap-3 p-3 bg-light rounded-3 mb-3 border">
                    <div>
                        <div class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Base Selling Price</div>
                        <div class="fs-4 fw-bold text-dark">RWF {{ number_format((float) ($product->price ?? 0), 0, '.', ',') }}</div>
                    </div>
                    @if($product->discount_enabled && $product->original_price)
                        <div class="border-start ps-3">
                            <div class="text-uppercase text-muted fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Original Price</div>
                            <div class="text-muted text-decoration-line-through fs-5">
                                RWF {{ number_format((float) $product->original_price, 0, '.', ',') }}
                            </div>
                        </div>
                        @if($product->hasValidDiscount())
                            <span class="badge bg-danger text-white px-2 py-1 ms-auto" style="font-size: 0.82rem;">
                                <i class="bi bi-percent me-1"></i> {{ $product->discount_percentage }}% Discount
                            </span>
                        @endif
                    @endif
                </div>

                {{-- Variations Table (Only displayed when product actually has variations) --}}
                @if($hasVariations)
                    <h6 class="fw-bold text-dark mt-4 mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-layers text-secondary"></i> Price Variations
                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.75rem;">
                            {{ count($variations) }} options
                        </span>
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Option Attribute</th>
                                    <th>Option Value</th>
                                    <th>Variation Price</th>
                                    <th class="text-end">Default Option</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($variations as $vIndex => $variation)
                                    @php
                                        $fLabel = is_array($variation) ? ($variation['feature_label'] ?? '—') : $variation->feature_label;
                                        $vValue = is_array($variation) ? ($variation['variation_value'] ?? '—') : $variation->variation_value;
                                        $vPrice = is_array($variation) ? ($variation['price'] ?? 0) : $variation->price;
                                        $isDef = is_array($variation) 
                                            ? filter_var($variation['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN) 
                                            : (bool)$variation->is_default;
                                    @endphp
                                    <tr>
                                        <td class="text-muted small">{{ $vIndex + 1 }}</td>
                                        <td class="fw-semibold text-dark">{{ $fLabel }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $vValue }}</span></td>
                                        <td class="fw-bold text-primary">RWF {{ number_format((float) $vPrice, 0, '.', ',') }}</td>
                                        <td class="text-end">
                                            @if($isDef)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check-circle-fill me-1"></i> Default
                                                </span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- 4. Inventory & Stock Management Card --}}
            <div class="review-card">
                <div class="review-card-header">
                    <h2 class="review-card-title">
                        <i class="bi bi-boxes text-primary"></i> Inventory & Stock Status
                    </h2>
                    <span class="badge {{ $product->tracks_inventory ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border' }}">
                        {{ $product->tracks_inventory ? 'Tracked Inventory' : 'Untracked Inventory' }}
                    </span>
                </div>

                {{-- Unapproved Initial Stock Notice (Strictly preserves string required by automated tests) --}}
                @if($product->tracks_inventory && array_key_exists('initial_stock_quantity', $product->pending_payload ?? []))
                    <div class="alert alert-info border-info d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
                        <i class="bi bi-info-circle-fill text-info fs-4 flex-shrink-0"></i>
                        <div>
                            <div class="fw-bold mb-1">Unapproved Initial Stock Registration</div>
                            <p class="mb-0">
                                Unapproved initial stock: {{ number_format((int) $product->pending_payload['initial_stock_quantity']) }} {{ $product->stock_unit ?: 'units' }}.
                                It remains unavailable until the product is approved.
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Stock Breakdown Metrics --}}
                <div class="stock-grid">
                    <div class="stock-card">
                        <div class="stock-card-label">Approved On-Hand</div>
                        <div class="stock-card-val text-dark">
                            {{ number_format((int) ($product->stock_quantity ?? 0)) }}
                            <small class="fs-6 fw-normal text-muted">{{ $product->stock_unit ?: 'units' }}</small>
                        </div>
                    </div>
                    <div class="stock-card">
                        <div class="stock-card-label">Reserved</div>
                        <div class="stock-card-val text-warning">
                            {{ number_format((int) ($product->reserved_quantity ?? 0)) }}
                            <small class="fs-6 fw-normal text-muted">{{ $product->stock_unit ?: 'units' }}</small>
                        </div>
                    </div>
                    <div class="stock-card">
                        <div class="stock-card-label">Customer Available</div>
                        <div class="stock-card-val text-success">
                            {{ number_format((int) ($product->availableQuantity() ?? 0)) }}
                            <small class="fs-6 fw-normal text-muted">{{ $product->stock_unit ?: 'units' }}</small>
                        </div>
                    </div>
                    <div class="stock-card">
                        <div class="stock-card-label">Low Stock Threshold</div>
                        <div class="stock-card-val text-muted">
                            {{ $product->low_stock_threshold ? number_format((int) $product->low_stock_threshold) : 'None' }}
                        </div>
                    </div>
                </div>

                {{-- Storage Locations Listing --}}
                @if(auth()->user()->hasPermission('locations.view'))
                    <h6 class="fw-bold text-dark mt-4 mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt text-secondary"></i> Storage Locations
                    </h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Warehouse</th>
                                    <th>District</th>
                                    <th>Shelf / Bin</th>
                                    <th class="text-end">On-Hand Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($product->locations as $location)
                                    <tr>
                                        <td class="fw-semibold text-dark">{{ $location->warehouse_name }}</td>
                                        <td>{{ $location->district ?: '—' }}</td>
                                        <td>{{ $location->shelf_reference ?: '—' }}</td>
                                        <td class="text-end fw-bold">{{ number_format((int) ($location->quantity ?? 0)) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted small fst-italic">
                                            No storage location recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Add Location Form --}}
                @if(auth()->user()->hasPermission('locations.manage') && $product->exists)
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.88rem;">Add Storage Location</h6>
                        <form method="POST" action="{{ route('marketplace.locations.store', $product) }}" class="row g-2">
                            @csrf
                            <div class="col-md-3">
                                <input name="warehouse_name" class="form-control form-control-sm" placeholder="Warehouse *" required>
                            </div>
                            <div class="col-md-3">
                                <input name="district" class="form-control form-control-sm" placeholder="District">
                            </div>
                            <div class="col-md-3">
                                <input name="shelf_reference" class="form-control form-control-sm" placeholder="Shelf / Bin ref">
                            </div>
                            <div class="col-md-2">
                                <input name="quantity" type="number" min="0" class="form-control form-control-sm" placeholder="Qty">
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-outline-dark btn-sm w-100" type="submit" title="Add Location">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Adjust Inventory Form --}}
                @if(auth()->user()->hasPermission('inventory.manage') && $product->exists)
                    <div class="p-3 bg-light rounded-3 border">
                        <h6 class="fw-bold text-dark mb-2" style="font-size: 0.88rem;">Adjust Physical Inventory</h6>
                        <form method="POST" action="{{ route('marketplace.inventory.adjust', $product) }}" class="row g-2">
                            @csrf
                            <div class="col-md-3">
                                <input name="delta" type="number" class="form-control form-control-sm" placeholder="+/- Delta *" required>
                            </div>
                            <div class="col-md-7">
                                <input name="note" class="form-control form-control-sm" placeholder="Adjustment reason or audit reference...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-primary btn-sm w-100 fw-semibold" type="submit">
                                    Adjust Stock
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            {{-- 5. Specifications & Features Card (Only rendered if specifications or features exist) --}}
            @if($hasSpecsOrFeatures)
                <div class="review-card">
                    <div class="review-card-header">
                        <h2 class="review-card-title">
                            <i class="bi bi-list-check text-primary"></i> Specifications & Key Features
                        </h2>
                    </div>

                    {{-- Technical Specifications Table --}}
                    @if($filteredSpecs->isNotEmpty())
                        <h6 class="fw-bold text-dark mb-2">Technical Specifications</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-striped align-middle mb-0 spec-table">
                                <thead>
                                    <tr>
                                        <th style="width: 35%;">Specification</th>
                                        <th>Details / Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($filteredSpecs as $spec)
                                        <tr>
                                            <td class="fw-semibold text-secondary">{{ $spec['label'] ?? '—' }}</td>
                                            <td class="text-dark">{{ $spec['value'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    {{-- Features List --}}
                    @if($filteredFeatures->isNotEmpty())
                        <h6 class="fw-bold text-dark mb-2">Key Highlights & Features</h6>
                        <div class="ps-1">
                            @foreach($filteredFeatures as $feature)
                                <div class="feature-item">
                                    <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                                    <span class="text-dark">{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Right Column: Administrative Decisions & Actions (4 cols) --}}
        <div class="col-lg-4">
            <div class="sticky-decision-column">

                {{-- Action Card 1: Review Decision (Primary Workflow) --}}
                <div class="review-card border-primary-subtle shadow-sm">
                    <div class="review-card-header">
                        <h2 class="review-card-title text-primary">
                            <i class="bi bi-shield-check"></i> Administrator Decision
                        </h2>
                    </div>

                    <p class="text-muted small mb-3">
                        Review the submitted details against catalog and quality guidelines, then select your decision.
                    </p>

                    @if(auth()->user()->hasPermission('products.approve') && $product->exists)
                        {{-- 1. Approve Product --}}
                        <form method="POST" action="{{ route('marketplace.products.approve', $product) }}" class="mb-3">
                            @csrf
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Optional Approval Note
                            </label>
                            <input name="note" class="form-control form-control-sm mb-2" placeholder="e.g. Approved per vendor standards">
                            <button class="btn btn-success btn-sm w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2" type="submit">
                                <i class="bi bi-check-circle-fill"></i> Approve Product
                            </button>
                        </form>

                        <div class="text-center my-3 position-relative">
                            <hr class="m-0">
                            <span class="bg-white px-2 text-muted position-absolute top-50 start-50 translate-middle small fw-semibold" style="font-size: 0.72rem;">
                                OR REQUEST ACTION
                            </span>
                        </div>

                        {{-- 2. Request Changes --}}
                        <form method="POST" action="{{ route('marketplace.products.changes', $product) }}" class="mb-3">
                            @csrf
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Requested Changes <span class="text-danger">*</span>
                            </label>
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Specify corrections or additional info needed..." required></textarea>
                            <button class="btn btn-warning btn-sm w-100 fw-bold text-dark py-2 d-flex align-items-center justify-content-center gap-2" type="submit">
                                <i class="bi bi-pencil-square"></i> Request Changes
                            </button>
                        </form>

                        {{-- 3. Reject Product --}}
                        <form method="POST" action="{{ route('marketplace.products.reject', $product) }}">
                            @csrf
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Rejection Reason <span class="text-danger">*</span>
                            </label>
                            <textarea name="note" class="form-control form-control-sm mb-2" rows="2" placeholder="Explain reason for rejection..." required></textarea>
                            <button class="btn btn-outline-danger btn-sm w-100 fw-bold py-2 d-flex align-items-center justify-content-center gap-2" type="submit">
                                <i class="bi bi-x-circle"></i> Reject Product
                            </button>
                        </form>
                    @else
                        <div class="alert alert-secondary py-2 px-3 mb-0 small text-muted">
                            <i class="bi bi-lock me-1"></i> You do not have permission to approve or reject this submission.
                        </div>
                    @endif
                </div>

                {{-- Action Card 2: Publication & Storefront Lifecycle --}}
                @if(auth()->user()->hasPermission('products.publish') && $product->exists)
                    <div class="review-card">
                        <div class="review-card-header">
                            <h2 class="review-card-title">
                                <i class="bi bi-globe text-primary"></i> Catalog Publication
                            </h2>
                            <span class="badge {{ $product->publication_status === 'published' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted border' }}">
                                {{ ucfirst((string) $product->publication_status) }}
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            Approved products must be published to become visible to customers in the marketplace catalog.
                        </p>

                        <div class="d-flex flex-column gap-2">
                            <form method="POST" action="{{ route('marketplace.products.publish', $product) }}">
                                @csrf
                                <button class="btn btn-primary btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-2" type="submit">
                                    <i class="bi bi-cloud-upload"></i> Publish to Shop
                                </button>
                            </form>

                            <form method="POST" action="{{ route('marketplace.products.unpublish', $product) }}">
                                @csrf
                                <button class="btn btn-outline-secondary btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-2" type="submit">
                                    <i class="bi bi-cloud-slash"></i> Unpublish Listing
                                </button>
                            </form>

                            <form method="POST" action="{{ route('marketplace.products.archive', $product) }}">
                                @csrf
                                <button class="btn btn-outline-dark btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-2" type="submit" onclick="return confirm('Archive this product?')">
                                    <i class="bi bi-archive"></i> Archive Product
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- Action Card 3: Vendor Assignment --}}
                @if(auth()->user()->hasPermission('products.assign_vendor') && $product->exists)
                    <div class="review-card">
                        <div class="review-card-header">
                            <h2 class="review-card-title">
                                <i class="bi bi-person-badge text-primary"></i> Vendor Assignment
                            </h2>
                        </div>

                        <form method="POST" action="{{ route('marketplace.products.vendor', $product) }}">
                            @csrf
                            <label class="form-label small fw-semibold text-muted mb-1">
                                Assigned Vendor Account
                            </label>
                            <select name="vendor_id" class="form-select form-select-sm mb-3">
                                <option value="">No vendor (Direct catalog)</option>
                                @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected($product->vendor_id == $vendor->id)>
                                        {{ $vendor->business_name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-dark btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center gap-2" type="submit">
                                <i class="bi bi-person-check"></i> Assign Vendor
                            </button>
                        </form>
                    </div>
                @endif

                {{-- Action Card 4: Review History Log --}}
                <div class="review-card">
                    <div class="review-card-header">
                        <h2 class="review-card-title">
                            <i class="bi bi-clock-history text-primary"></i> Review History
                        </h2>
                    </div>

                    <ul class="list-unstyled review-history-timeline mb-0">
                        @forelse($product->approvalHistories as $history)
                            <li class="review-history-item">
                                <div class="review-history-point"></div>
                                <div class="review-history-content">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="text-dark font-monospace text-capitalize" style="font-size: 0.82rem;">
                                            {{ str_replace('_', ' ', (string) $history->action) }}
                                        </strong>
                                        <span class="text-muted" style="font-size: 0.72rem;">
                                            {{ $history->created_at ? $history->created_at->format('M d, Y H:i') : '—' }}
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="bi bi-person me-1"></i> {{ $history->actor->name ?? 'System' }}
                                    </div>
                                    @if($history->note)
                                        <div class="review-history-note mt-2 p-2 rounded small text-dark">
                                            {{ $history->note }}
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="text-muted small fst-italic py-2">
                                No review history recorded yet.
                            </li>
                        @endforelse
                    </ul>
                </div>

            </div>
        </div>

    </div>

</div>

{{-- Image Zoom Preview Modal --}}
@if($hasImages)
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header py-2 px-3 border-bottom">
                    <h5 class="modal-title fs-6 fw-bold text-dark" id="imagePreviewModalLabel">
                        {{ $product->product_name }} — Image Preview
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-2 text-center bg-dark rounded-bottom">
                    <img id="modalPreviewImage" src="{{ $primaryImageSrc }}" class="img-fluid rounded" style="max-height: 80vh; object-fit: contain;" alt="{{ $product->product_name }}">
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainImage = document.getElementById('reviewMainImage');
        const modalImage = document.getElementById('modalPreviewImage');
        const thumbnailButtons = document.querySelectorAll('.review-thumb-btn');

        thumbnailButtons.forEach(button => {
            button.addEventListener('click', function () {
                const newSrc = this.getAttribute('data-img-src');
                if (mainImage && newSrc) {
                    mainImage.src = newSrc;
                }
                if (modalImage && newSrc) {
                    modalImage.src = newSrc;
                }
                thumbnailButtons.forEach(btn => btn.classList.remove('active-thumb'));
                this.classList.add('active-thumb');
            });
        });

        if (mainImage && modalImage) {
            mainImage.addEventListener('click', function () {
                modalImage.src = mainImage.src;
            });
        }
    });
</script>
@endpush
