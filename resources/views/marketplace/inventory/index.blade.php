@extends('front.layouts.admin')
@section('page-title', 'Inventory')
@section('content')
@php
    $imageUrl = fn ($path) => $path ? asset('assets/'.ltrim(str_replace('public/assets/', '', $path), '/')) : null;
    $statusBadge = function ($status) {
        return match ($status) {
            'approved', 'published', 'in_stock' => 'text-bg-success',
            'pending', 'pending_approval', 'low_stock' => 'text-bg-warning',
            'rejected', 'out_of_stock', 'archived' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    };
@endphp
<style>
    .inventory-page { max-width: 1500px; }
    .inventory-section { border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; }
    .inventory-section-heading { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; }
    .inventory-section-body { padding: 16px 20px; }
    .inventory-table th { white-space: nowrap; font-size: .78rem; color: #475569; }
    .inventory-table td { vertical-align: middle; font-size: .86rem; }
    .inventory-product-image { width: 44px; height: 44px; border-radius: 7px; object-fit: cover; }
    .inventory-product-placeholder { display:flex; width:44px; height:44px; align-items:center; justify-content:center; border-radius:7px; background:#f1f5f9; color:#64748b; }
    .inventory-filter-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(165px,1fr)); gap:10px; align-items:end; }
    .inventory-section summary { list-style:none; cursor:pointer; }
    .inventory-section summary::-webkit-details-marker { display:none; }
    .inventory-section summary::after { content:'+'; margin-left:auto; color:#64748b; font-size:1.2rem; }
    .inventory-section[open] summary::after { content:'−'; }
    @media(max-width:991.98px){.inventory-filter-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:575.98px){.inventory-section-heading,.inventory-section-body{padding:14px}.inventory-filter-row{grid-template-columns:1fr}.inventory-filter-actions{display:flex}.inventory-filter-actions .btn{flex:1}}
</style>
<div class="container-fluid py-4 inventory-page">
    @include('marketplace.partials.flash')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small text-muted mb-1">Marketplace / Inventory</div>
            <h1 class="h3 mb-1">Inventory Management</h1>
            <p class="text-muted mb-0">Manage approved stock and review inventory changes.</p>
        </div>
        @if(auth()->user()->hasPermission('inventory.manage'))
            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Inventory
                </button>
                <button class="btn btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#removeInventoryModal">
                    <i class="bi bi-dash-lg me-1" aria-hidden="true"></i> Remove Inventory
                </button>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold">Please correct the following:</div>
            <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="inventory-section mb-4">
        <div class="inventory-section-heading"><h2 class="h5 mb-0">Inventory</h2></div>
        <div class="inventory-section-body">
            <form method="GET" action="{{ route('marketplace.inventory.index') }}" class="mb-3">
                <div class="inventory-filter-row">
                    <div><label class="form-label small fw-semibold mb-1" for="inventorySearch">Search</label><input id="inventorySearch" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}" placeholder="Product name or SKU"></div>
                    <div><label class="form-label small fw-semibold mb-1" for="categoryFilter">Product type / category</label>
                        <select class="form-select form-select-sm" id="categoryFilter" name="shop_category_id">
                            <option value="">All categories</option>
                            @foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['shop_category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach
                        </select>
                    </div>
                    @if(auth()->user()->hasPermission('vendors.view'))
                        <div><label class="form-label small fw-semibold mb-1" for="vendorFilter">Vendor</label>
                            <select class="form-select form-select-sm" id="vendorFilter" name="vendor_id">
                                <option value="">All vendors</option>
                                @foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(($filters['vendor_id'] ?? '') == $vendor->id)>{{ $vendor->business_name }}</option>@endforeach
                            </select>
                        </div>
                    @endif
                    <div><label class="form-label small fw-semibold mb-1" for="stockFilter">Stock status</label>
                        <select class="form-select form-select-sm" id="stockFilter" name="stock_status">
                            <option value="">All stock statuses</option>
                            @foreach(['in_stock' => 'In stock', 'low_stock' => 'Low stock', 'out_of_stock' => 'Out of stock', 'finished' => 'Finished'] as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['stock_status'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inventory-filter-actions gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ route('marketplace.inventory.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    </div>
                </div>
            </form>
            @if($unitTotals->isNotEmpty())
                <div class="small text-muted mb-3">Available stock by unit:
                    @foreach($unitTotals as $unitTotal)
                        <span class="badge text-bg-light border text-dark me-1">{{ $unitTotal->unit_name }}: {{ number_format((int) $unitTotal->available_quantity) }}</span>
                    @endforeach
                </div>
            @endif
            <div class="table-responsive">
                <table class="table table-hover inventory-table mb-0">
                    <thead><tr><th>Product</th><th>Product type / category</th><th>Current approved stock</th><th>Available</th><th>Status</th><th>Last updated</th>@if(auth()->user()->hasPermission('inventory.manage'))<th>Action</th>@endif</tr></thead>
                    <tbody>
                    @forelse($products as $product)
                        @php
                            $available = $product->availableQuantity();
                            $stockLabel = $product->is_finished ? 'Finished' : ($product->status === 'out_of_stock' || $available <= 0 ? 'Out of stock' : ($available <= $product->low_stock_threshold ? 'Low stock' : 'In stock'));
                            $stockKey = $product->is_finished ? 'archived' : ($stockLabel === 'Low stock' ? 'low_stock' : ($stockLabel === 'Out of stock' ? 'out_of_stock' : 'in_stock'));
                        @endphp
                        <tr>
                            <td><div class="d-flex align-items-center gap-2">
                                @if($product->thumbnail)<img class="inventory-product-image" src="{{ $imageUrl($product->thumbnail) }}" alt="{{ $product->product_name }}">
                                @else <span class="inventory-product-placeholder" aria-hidden="true"><i class="bi bi-box"></i></span>@endif
                                <span><span class="fw-semibold">{{ $product->product_name }}</span><span class="d-block small text-muted">{{ $product->sku ?: 'No SKU' }}</span></span>
                            </div></td>
                            <td>{{ $product->category?->name ?? '—' }}@if($product->subcategory)<span class="d-block small text-muted">{{ $product->subcategory->name }}</span>@endif</td>
                            <td class="fw-semibold">{{ number_format($product->stock_quantity) }} {{ $product->stock_unit }}</td>
                            <td>{{ number_format($available) }} {{ $product->stock_unit }}</td>
                            <td><span class="badge {{ $statusBadge($stockKey) }}">{{ $stockLabel }}</span></td>
                            <td>{{ $product->updated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            @if(auth()->user()->hasPermission('inventory.manage'))
                                <td><button type="button" class="btn btn-sm btn-outline-primary inventory-edit-button"
                                    data-bs-toggle="modal" data-bs-target="#editInventoryModal"
                                    data-update-url="{{ route('marketplace.inventory.update', $product) }}"
                                    data-product-id="{{ $product->id }}"
                                    data-product-name="{{ $product->product_name }}"
                                    data-sku="{{ $product->sku }}"
                                    data-stock="{{ number_format($product->stock_quantity) }} {{ $product->stock_unit }}"
                                    data-category-id="{{ $product->shop_category_id }}"
                                    data-notes="{{ $product->inventory_notes }}">
                                    <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>View / Edit
                                </button></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->hasPermission('inventory.manage') ? 7 : 6 }}" class="text-center text-muted py-4">No tracked products match these filters.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $products->links() }}</div>
        </div>
    </section>

    @if(auth()->user()->hasPermission('inventory.manage'))
        <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-labelledby="addInventoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('marketplace.inventory.store') }}" class="modal-content" id="addInventoryForm">
                    @csrf
                    <input type="hidden" name="inventory_form" value="add">
                    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="modal-header">
                        <div><h2 class="modal-title fs-5" id="addInventoryTitle">Add Inventory</h2><p class="small text-muted mb-0">Stock updates are saved as an inventory movement.</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="addInventoryCategory">Product type / category</label>
                            <select id="addInventoryCategory" name="shop_category_id" class="form-select" required>
                                <option value="">Choose a category</option>
                                @foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('inventory_form') === 'add' && old('shop_category_id') == $category->id)>{{ $category->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="addInventoryProduct">Product</label>
                            <select id="addInventoryProduct" name="product_id" class="form-select" data-products-url="{{ route('marketplace.inventory.categories.products', ['categoryId' => 0]) }}" data-empty-label="No products exist in this category." required disabled>
                                <option value="">Choose a category first</option>
                            </select>
                            <div class="small mt-1" id="addInventoryProductStatus" role="status" aria-live="polite"></div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6"><label class="form-label" for="currentInventoryStock">Current approved stock</label><input id="currentInventoryStock" class="form-control" value="—" readonly></div>
                            <div class="col-sm-6"><label class="form-label" for="inventoryQuantity">Quantity to add</label><input id="inventoryQuantity" name="quantity" type="number" class="form-control" min="1" max="2147483647" step="1" value="{{ old('inventory_form') === 'add' ? old('quantity') : '' }}" required></div>
                        </div>
                        <div class="small text-muted mb-3" id="addInventorySku">SKU: — · Unit: —</div>
                        <div class="alert alert-light border py-2 mb-3" role="status" aria-live="polite"><span id="inventoryStockPreview">Choose a product to preview the projected stock.</span></div>
                        <div class="mb-3"><label class="form-label" for="inventoryReason">Reason</label><input id="inventoryReason" name="reason" class="form-control" maxlength="255" value="{{ old('inventory_form') === 'add' ? old('reason') : '' }}" placeholder="e.g. Supplier delivery" required></div>
                        <div><label class="form-label" for="inventoryAddNotes">Notes <span class="text-muted">(optional)</span></label><textarea id="inventoryAddNotes" name="notes" rows="2" maxlength="1000" class="form-control" placeholder="Additional details">{{ old('inventory_form') === 'add' ? old('notes') : '' }}</textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Inventory</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="removeInventoryModal" tabindex="-1" aria-labelledby="removeInventoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('marketplace.inventory.remove') }}" class="modal-content" id="removeInventoryForm">
                    @csrf
                    <input type="hidden" name="inventory_form" value="remove">
                    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <div class="modal-header">
                        <div><h2 class="modal-title fs-5" id="removeInventoryTitle">Remove Inventory</h2><p class="small text-muted mb-0">Stock is revalidated before the removal is recorded.</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label" for="removeInventoryCategory">Product type / category</label>
                            <select id="removeInventoryCategory" name="shop_category_id" class="form-select" required>
                                <option value="">Choose a category</option>
                                @foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('inventory_form') === 'remove' && old('shop_category_id') == $category->id)>{{ $category->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="removeInventoryProduct">Product</label>
                            <select id="removeInventoryProduct" name="product_id" class="form-select" data-products-url="{{ route('marketplace.inventory.categories.products', ['categoryId' => 0]) }}" data-empty-label="No inventory-tracked products with available stock exist in this category." required disabled>
                                <option value="">Choose a category first</option>
                            </select>
                            <div class="small mt-1" id="removeInventoryProductStatus" role="status" aria-live="polite"></div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-sm-6"><label class="form-label" for="removeInventoryCurrentStock">Current approved stock</label><input id="removeInventoryCurrentStock" class="form-control" value="—" readonly></div>
                            <div class="col-sm-6"><label class="form-label" for="removeInventoryQuantity">Quantity to remove</label><input id="removeInventoryQuantity" name="quantity" type="number" class="form-control" min="1" max="2147483647" step="1" value="{{ old('inventory_form') === 'remove' ? old('quantity') : '' }}" required></div>
                        </div>
                        <div class="small text-muted mb-3" id="removeInventoryAvailability">SKU: — · Unit: — · Available to remove: —</div>
                        <div class="alert alert-light border py-2 mb-3" role="status" aria-live="polite"><span id="removeInventoryPreview">Choose a product to preview the projected stock.</span></div>
                        <div class="mb-3"><label class="form-label" for="removeInventoryReason">Reason for removal</label><input id="removeInventoryReason" name="reason" class="form-control" maxlength="255" value="{{ old('inventory_form') === 'remove' ? old('reason') : '' }}" placeholder="e.g. Damaged goods, expired products, stock correction" required></div>
                        <div><label class="form-label" for="removeInventoryNotes">Notes <span class="text-muted">(optional)</span></label><textarea id="removeInventoryNotes" name="notes" rows="2" maxlength="1000" class="form-control" placeholder="Additional details">{{ old('inventory_form') === 'remove' ? old('notes') : '' }}</textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove Stock</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content" id="editInventoryForm">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="edit_product_id" id="editInventoryProductId">
                    <div class="modal-header">
                        <div><h2 class="modal-title fs-5" id="editInventoryTitle">Inventory details</h2><p class="small text-muted mb-0">Current stock can only be changed through an inventory movement.</p></div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><div class="small text-muted">Product</div><div class="fw-semibold" id="editInventoryProductName"></div><div class="small text-muted" id="editInventorySku"></div></div>
                        <div class="mb-3"><label class="form-label" for="editInventoryStock">Current approved stock</label><input id="editInventoryStock" class="form-control" readonly></div>
                        <div class="mb-3"><label class="form-label" for="editInventoryCategory">Product type / category</label>
                            <select id="editInventoryCategory" name="shop_category_id" class="form-select" required>
                                @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                            </select>
                        </div>
                        <div><label class="form-label" for="editInventoryNotes">Inventory notes</label><textarea id="editInventoryNotes" name="inventory_notes" rows="3" maxlength="5000" class="form-control"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <section class="inventory-section mb-4">
        <div class="inventory-section-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div><h2 class="h5 mb-1">Inventory Approval Requests</h2><p class="small text-muted mb-0">Vendor requests do not affect official stock until approved.</p></div>
            <form method="GET" action="{{ route('marketplace.inventory.index') }}" class="d-flex gap-2 align-items-center">
                @foreach(request()->except(['request_status', 'request_page']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <label class="small text-muted" for="requestStatus">Status</label>
                <select class="form-select form-select-sm" id="requestStatus" name="request_status" onchange="this.form.submit()">
                    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['request_status'] ?? 'pending') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <div class="inventory-section-body">
            <div class="table-responsive"><table class="table table-hover inventory-table mb-0">
                <thead><tr><th>Product / SKU</th><th>Vendor</th><th>Current at request</th><th>Requested change</th><th>Reason</th><th>Requested</th><th>Status</th><th>Reviewed by / date</th>@if(auth()->user()->hasPermission('inventory.manage'))<th>Review action</th>@endif</tr></thead>
                <tbody>
                @forelse($requests as $inventoryRequest)
                    @php
                        $requestUnit = $inventoryRequest->product?->stock_unit;
                        $changeText = match($inventoryRequest->change_type) {
                            'addition' => '+'.number_format($inventoryRequest->quantity),
                            'reduction' => '-'.number_format($inventoryRequest->quantity),
                            default => 'Set to '.number_format($inventoryRequest->quantity),
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $inventoryRequest->product?->product_name ?? 'Deleted product' }}<div class="small text-muted">{{ $inventoryRequest->product?->sku ?? '—' }}</div></td>
                        <td>{{ $inventoryRequest->vendor?->business_name ?? '—' }}</td>
                        <td>{{ number_format($inventoryRequest->stock_before) }} {{ $requestUnit }}</td>
                        <td>{{ ucfirst($inventoryRequest->change_type) }} · {{ $changeText }} {{ $requestUnit }}</td>
                        <td class="text-break">{{ $inventoryRequest->reason }}@if($inventoryRequest->review_note)<div class="small text-danger mt-1">Review: {{ $inventoryRequest->review_note }}</div>@endif</td>
                        <td>{{ $inventoryRequest->created_at->format('Y-m-d H:i') }}</td>
                        <td><span class="badge {{ $statusBadge($inventoryRequest->status) }}">{{ ucfirst($inventoryRequest->status) }}</span></td>
                        <td>{{ $inventoryRequest->reviewer?->name ?? '—' }}<div class="small text-muted">{{ $inventoryRequest->reviewed_at?->format('Y-m-d H:i') ?? '—' }}</div></td>
                        @if(auth()->user()->hasPermission('inventory.manage'))
                            <td>
                                @if($inventoryRequest->status === 'pending')
                                    <div class="d-flex flex-column gap-2">
                                        <form method="POST" action="{{ route('marketplace.inventory-requests.approve', $inventoryRequest) }}" onsubmit="return confirm('Approve this stock request and update official inventory?')">@csrf
                                            <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('marketplace.inventory-requests.reject', $inventoryRequest) }}" onsubmit="return confirm('Reject this inventory request?')">@csrf
                                            <input name="review_note" class="form-control form-control-sm mb-1" placeholder="Rejection reason" required maxlength="2000" aria-label="Rejection reason">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">Reviewed</span>
                                @endif
                                @error('request')<div class="text-danger small">{{ $message }}</div>@enderror
                                @error('review_note')<div class="text-danger small">{{ $message }}</div>@enderror
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->hasPermission('inventory.manage') ? 9 : 8 }}" class="text-center text-muted py-4">No {{ $filters['request_status'] ?? 'pending' }} inventory requests.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="mt-3">{{ $requests->links() }}</div>
        </div>
    </section>

    <section class="inventory-section">
        <div class="inventory-section-heading"><h2 class="h5 mb-1">Stock Movement History</h2><p class="small text-muted mb-0">Order deductions, returns, reservations, releases, staff adjustments, and approved vendor changes.</p></div>
        <div class="inventory-section-body">
            <form method="GET" action="{{ route('marketplace.inventory.index') }}" class="mb-3">
                @foreach(request()->except(['movement_product', 'movement_vendor', 'movement_type', 'movement_from', 'movement_to', 'movement_page']) as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <div class="inventory-filter-grid">
                    <div><label class="form-label small fw-semibold" for="movementProduct">Product</label><select name="movement_product" id="movementProduct" class="form-select form-select-sm"><option value="">All products</option>
                        @foreach($productOptions as $option)<option value="{{ $option->id }}" @selected(($filters['movement_product'] ?? '') == $option->id)>{{ $option->product_name }}{{ $option->sku ? ' · '.$option->sku : '' }}</option>@endforeach
                    </select></div>
                    <div><label class="form-label small fw-semibold" for="movementVendor">Vendor</label><select name="movement_vendor" id="movementVendor" class="form-select form-select-sm"><option value="">All vendors</option><option value="0" @selected(($filters['movement_vendor'] ?? '') == '0')>TRESCA Hub</option>
                        @foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(($filters['movement_vendor'] ?? '') == $vendor->id)>{{ $vendor->business_name }}</option>@endforeach
                    </select></div>
                    <div><label class="form-label small fw-semibold" for="movementType">Movement type</label><select name="movement_type" id="movementType" class="form-select form-select-sm"><option value="">All movement types</option>
                        @foreach(['addition', 'removal', 'adjustment', 'reservation', 'release', 'sale', 'return'] as $type)<option value="{{ $type }}" @selected(($filters['movement_type'] ?? '') === $type)>{{ ucfirst($type) }}</option>@endforeach
                    </select></div>
                    <div><label class="form-label small fw-semibold" for="movementFrom">From</label><input type="date" name="movement_from" id="movementFrom" class="form-control form-control-sm" value="{{ $filters['movement_from'] ?? '' }}"></div>
                    <div><label class="form-label small fw-semibold" for="movementTo">To</label><input type="date" name="movement_to" id="movementTo" class="form-control form-control-sm" value="{{ $filters['movement_to'] ?? '' }}"></div>
                </div>
                <div class="d-flex gap-2 mt-3"><button class="btn btn-sm btn-primary">Filter movements</button><a class="btn btn-sm btn-outline-secondary" href="{{ route('marketplace.inventory.index') }}">Clear all filters</a></div>
            </form>
            <div class="table-responsive"><table class="table table-hover inventory-table mb-0">
                <thead><tr><th>Date</th><th>Product / SKU</th><th>Vendor</th><th>Movement</th><th>Change</th><th>Stock before</th><th>Stock after</th><th>Actor</th><th>Reason / reference</th></tr></thead>
                <tbody>
                @forelse($movements as $movement)
                    <tr>
                        <td>{{ $movement->created_at->format('Y-m-d H:i') }}</td>
                        <td>{{ $movement->product?->product_name ?? 'Deleted product' }}<div class="small text-muted">{{ $movement->product?->sku ?? '—' }}</div></td>
                        <td>{{ $movement->product?->vendor?->business_name ?? 'TRESCA Hub' }}</td>
                        <td>{{ ucfirst($movement->type) }}</td>
                        <td>{{ $movement->quantity_delta > 0 ? '+' : '' }}{{ number_format($movement->quantity_delta) }} {{ $movement->product?->stock_unit }}</td>
                        <td>{{ number_format($movement->stock_before) }}</td><td>{{ number_format($movement->stock_after) }}</td>
                        <td>{{ $movement->actor?->name ?? 'System' }}</td>
                        <td class="text-break">{{ $movement->note ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No stock movements match these filters.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="mt-3">{{ $movements->links() }}</div>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const numberFormat = new Intl.NumberFormat();

    async function configureInventoryForm(settings) {
        const form = document.getElementById(settings.formId);
        if (!form) return;

        const category = document.getElementById(settings.categoryId);
        const product = document.getElementById(settings.productId);
        const quantity = document.getElementById(settings.quantityId);
        const currentStock = document.getElementById(settings.currentStockId);
        const preview = document.getElementById(settings.previewId);
        const status = document.getElementById(settings.statusId);
        const detail = document.getElementById(settings.detailId);
        let productsById = new Map();
        let requestSequence = 0;

        function updatePreview() {
            const selectedProduct = productsById.get(product.value);
            if (!selectedProduct) {
                currentStock.value = '—';
                detail.textContent = settings.mode === 'remove'
                    ? 'SKU: — · Unit: — · Available to remove: —'
                    : 'SKU: — · Unit: —';
                preview.textContent = 'Choose a product to preview the projected stock.';
                quantity.removeAttribute('max');
                return;
            }

            const stock = selectedProduct.current_stock;
            const available = selectedProduct.available_stock;
            const unit = selectedProduct.unit ? ' ' + selectedProduct.unit : '';
            const amount = Math.max(0, Number(quantity.value) || 0);
            currentStock.value = numberFormat.format(stock) + unit;
            quantity.max = String(settings.mode === 'remove' ? available : 2147483647);
            if (settings.mode === 'remove') {
                detail.textContent = 'SKU: ' + (selectedProduct.sku || '—') + ' · Unit: ' + (selectedProduct.unit || '—') + ' · Available to remove: ' + numberFormat.format(available) + unit;
                preview.textContent = 'Current stock: ' + numberFormat.format(stock) + unit + ' − Quantity to remove: ' + numberFormat.format(amount) + unit + ' = Projected stock: ' + numberFormat.format(stock - amount) + unit;
            } else {
                detail.textContent = 'SKU: ' + (selectedProduct.sku || '—') + ' · Unit: ' + (selectedProduct.unit || '—');
                preview.textContent = 'Current stock: ' + numberFormat.format(stock) + unit + ' + Quantity to add: ' + numberFormat.format(amount) + unit + ' = Projected stock: ' + numberFormat.format(stock + amount) + unit;
            }
        }

        async function loadProducts(categoryId, selectedId) {
            const sequence = ++requestSequence;
            productsById = new Map();
            product.replaceChildren(new Option(categoryId ? 'Loading products…' : 'Choose a category first', ''));
            product.disabled = true;
            status.className = 'small mt-1 text-muted';
            status.textContent = '';
            updatePreview();
            if (!categoryId) return;

            try {
                const productsUrl = product.dataset.productsUrl.replace(/\/0\/products$/, '/' + encodeURIComponent(categoryId) + '/products');
                const response = await fetch(productsUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin'
                });
                let payload;
                try {
                    payload = await response.json();
                } catch (error) {
                    throw new Error('The product list response was invalid. Please retry.');
                }
                if (!response.ok) {
                    throw new Error(payload.message || 'Products could not be loaded (HTTP ' + response.status + '). Please retry.');
                }
                if (!Array.isArray(payload.products)) {
                    throw new Error('The product list response had an unexpected format. Please retry.');
                }
                if (sequence !== requestSequence) return;

                const eligibleProducts = settings.mode === 'remove'
                    ? payload.products.filter(function (item) {
                        return item.tracks_inventory && item.available_stock > 0;
                    })
                    : payload.products;
                if (eligibleProducts.length === 0) {
                    product.replaceChildren(new Option(product.dataset.emptyLabel, ''));
                    status.className = 'small mt-1 text-muted';
                    status.textContent = product.dataset.emptyLabel;
                    return;
                }

                product.replaceChildren(new Option('Choose a product', ''));
                eligibleProducts.forEach(function (item) {
                    productsById.set(String(item.id), item);
                    const label = item.name + (item.sku ? ' · ' + item.sku : '');
                    product.add(new Option(label, item.id));
                });
                product.disabled = false;
                status.textContent = eligibleProducts.length + (eligibleProducts.length === 1 ? ' product loaded.' : ' products loaded.');
                if (selectedId && productsById.has(String(selectedId))) {
                    product.value = String(selectedId);
                }
                updatePreview();
            } catch (error) {
                if (sequence !== requestSequence) return;
                product.replaceChildren(new Option('Unable to load products. Retry by changing category.', ''));
                status.className = 'small mt-1 text-danger';
                status.textContent = error instanceof Error ? error.message : 'Products could not be loaded. Please retry.';
            }
        }

        category.addEventListener('change', function () {
            product.value = '';
            loadProducts(category.value, null);
        });
        product.addEventListener('change', updatePreview);
        quantity.addEventListener('input', updatePreview);
        form.addEventListener('submit', function () {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = settings.mode === 'remove' ? 'Removing…' : 'Saving…';
        });

        if (category.value) {
            await loadProducts(category.value, settings.savedProduct);
        }
    }

    const addInventoryReady = configureInventoryForm({
        formId: 'addInventoryForm',
        mode: 'add',
        categoryId: 'addInventoryCategory',
        productId: 'addInventoryProduct',
        quantityId: 'inventoryQuantity',
        currentStockId: 'currentInventoryStock',
        previewId: 'inventoryStockPreview',
        statusId: 'addInventoryProductStatus',
        detailId: 'addInventorySku',
        savedProduct: @json(old('inventory_form') === 'add' ? old('product_id') : null)
    });
    const removeInventoryReady = configureInventoryForm({
        formId: 'removeInventoryForm',
        mode: 'remove',
        categoryId: 'removeInventoryCategory',
        productId: 'removeInventoryProduct',
        quantityId: 'removeInventoryQuantity',
        currentStockId: 'removeInventoryCurrentStock',
        previewId: 'removeInventoryPreview',
        statusId: 'removeInventoryProductStatus',
        detailId: 'removeInventoryAvailability',
        savedProduct: @json(old('inventory_form') === 'remove' ? old('product_id') : null)
    });

    @if($errors->any() && old('inventory_form') === 'add')
        addInventoryReady.then(function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('addInventoryModal')).show();
        });
    @elseif($errors->any() && old('inventory_form') === 'remove')
        removeInventoryReady.then(function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('removeInventoryModal')).show();
        });
    @endif

    const editModal = document.getElementById('editInventoryModal');
    if (editModal) {
        const editForm = document.getElementById('editInventoryForm');
        const editId = document.getElementById('editInventoryProductId');
        const editCategory = document.getElementById('editInventoryCategory');
        const editNotes = document.getElementById('editInventoryNotes');

        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;
            editForm.action = button.dataset.updateUrl;
            editId.value = button.dataset.productId;
            document.getElementById('editInventoryProductName').textContent = button.dataset.productName;
            document.getElementById('editInventorySku').textContent = button.dataset.sku ? 'SKU: ' + button.dataset.sku : 'No SKU';
            document.getElementById('editInventoryStock').value = button.dataset.stock;
            editCategory.value = button.dataset.categoryId;
            editNotes.value = button.dataset.notes || '';
        });

        editForm.addEventListener('submit', function () {
            const button = editForm.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Saving…';
        });

        @if(old('edit_product_id') && ($errors->has('edit_product_id') || $errors->has('inventory_notes') || $errors->has('shop_category_id')))
            const editProductId = String(@json(old('edit_product_id')));
            const editButton = Array.from(document.querySelectorAll('.inventory-edit-button')).find(function (button) {
                return button.dataset.productId === editProductId;
            });
            if (editButton) {
                bootstrap.Modal.getOrCreateInstance(editModal).show();
                editForm.action = editButton.dataset.updateUrl;
                editId.value = editButton.dataset.productId;
                document.getElementById('editInventoryProductName').textContent = editButton.dataset.productName;
                document.getElementById('editInventorySku').textContent = editButton.dataset.sku ? 'SKU: ' + editButton.dataset.sku : 'No SKU';
                document.getElementById('editInventoryStock').value = editButton.dataset.stock;
                editCategory.value = @json(old('shop_category_id'));
                editNotes.value = @json(old('inventory_notes', ''));
            }
        @endif
    }
});
</script>
@endsection
