@php
    $vendorMode = $vendorMode ?? false;
    $editing = $shopProduct->exists;
    $statusValue = old('status', $shopProduct->is_finished ? 'finished' : ($shopProduct->status === 'out_of_stock' ? 'out_of_stock' : 'active'));
    $thumbnailRemoved = (bool) old('remove_thumbnail', false);
    $galleryItems = old('gallery_order');
    $galleryItems = is_array($galleryItems)
        ? collect($galleryItems)->filter(fn ($token) => str_starts_with($token, 'existing:'))->map(fn ($token) => substr($token, 9))->values()->all()
        : ($shopProduct->images ?? []);
    $assetUrl = fn ($path) => asset('assets/'.ltrim(str_replace('public/assets/', '', $path), '/'));
    $specifications = old('specifications', $shopProduct->specifications ?? []);
    $features = old('features', $shopProduct->features ?? []);
    $tracksInventory = (bool) old('tracks_inventory', $shopProduct->tracks_inventory ?? false);
    $inventoryEnabled = $vendorMode && $editing
        ? (bool) $shopProduct->tracks_inventory
        : $tracksInventory;
    $productListRoute = $vendorMode ? route('vendor.products.index') : route('admin.shop-products.index');
    $productActionRoute = $vendorMode
        ? ($editing ? route('vendor.products.update', $shopProduct->slug) : route('vendor.products.store'))
        : ($editing ? route('admin.shop-products.update', $shopProduct) : route('admin.shop-products.store'));
@endphp

@push('styles')
    <style>
        .product-form-page { max-width: 1040px; }
        .product-form-surface { overflow: hidden; }
        .product-form-section { padding: 22px 26px; }
        .product-form-section + .product-form-section { border-top: 1px solid #e9edf2; }
        .product-form-section-heading { margin-bottom: 18px; }
        .product-form-section-heading h2 { margin: 0; font-size: 1rem; font-weight: 700; }
        .product-form .form-label { margin-bottom: 6px; font-size: .875rem; font-weight: 600; }
        .product-form .form-control, .product-form .form-select { min-height: 42px; }
        .product-form textarea.form-control { min-height: 112px; }
        .product-form .form-text { font-size: .8rem; }
        .product-form .input-group-text { min-width: 62px; justify-content: center; }
        .product-form-field { margin-bottom: 16px; }
        .product-form-field:last-child { margin-bottom: 0; }
        .product-form-option { min-height: 42px; }
        .product-thumbnail-preview {
            display: flex;
            width: 160px;
            height: 160px;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #dfe4eb;
            border-radius: 10px;
            background: #f8f9fb;
        }
        .product-thumbnail-preview img { width: 100%; height: 100%; object-fit: contain; }
        .product-gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
            gap: 12px;
        }
        .product-gallery-item { min-width: 0; }
        .product-gallery-thumb {
            position: relative;
            display: flex;
            width: 100%;
            aspect-ratio: 1;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #dfe4eb;
            border-radius: 8px;
            background: #f8f9fb;
        }
        .product-gallery-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .product-gallery-item .gallery-file-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .product-gallery-actions { display: flex; gap: 4px; margin-top: 6px; }
        .product-gallery-actions .btn { padding: 3px 7px; }
        .product-repeat-row { margin-bottom: 8px; }
        .product-empty-repeat { margin: 0 0 8px; color: #6c757d; font-size: .85rem; }
        .product-action-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .product-action-bar .btn { min-height: 40px; }
        @media (max-width: 767.98px) {
            .product-form-section { padding: 18px 16px; }
            .product-form-section-heading { margin-bottom: 14px; }
        }
        @media (max-width: 575.98px) {
            .product-action-bar { align-items: stretch; flex-direction: column-reverse; }
            .product-action-bar .btn { width: 100%; }
        }
    </style>
@endpush

<div class="container-fluid py-4">
    <div class="product-form-page mx-auto">
        <div class="mb-4">
            <div class="text-muted small mb-2">
                <a href="{{ $productListRoute }}" class="text-decoration-none text-muted">{{ $vendorMode ? 'My Products' : 'Shop Products' }}</a>
                <span class="mx-1">/</span><span>{{ $editing ? 'Edit' : 'Create' }}</span>
            </div>
            <h1 class="h3 mb-1">{{ $editing ? 'Edit Product' : ($vendorMode ? 'Submit a Product' : 'Create Product') }}</h1>
            <p class="text-muted mb-0">Manage product details, pricing, availability, and images.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 mb-3" role="alert">Please review the highlighted fields and correct the errors below.</div>
        @endif

        <form action="{{ $productActionRoute }}"
              method="POST" enctype="multipart/form-data" id="productForm" class="product-form">
            @csrf
            @if($editing) @method('PUT') @endif

            <div class="card border shadow-sm product-form-surface">
                <section class="product-form-section" aria-labelledby="product-information-heading">
                    <div class="product-form-section-heading">
                        <h2 id="product-information-heading">Product Information</h2>
                    </div>
                    <div class="row gx-3">
                        <div class="col-md-6 product-form-field">
                            <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input id="product_name" name="product_name" maxlength="255" required
                                   class="form-control @error('product_name') is-invalid @enderror"
                                   value="{{ old('product_name', $shopProduct->product_name) }}">
                            @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 product-form-field">
                            <label for="status" class="form-label">Product Status <span class="text-danger">*</span></label>
                            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="active" @selected($statusValue === 'active' || $statusValue === 'in_stock')>Active</option>
                                <option value="out_of_stock" @selected($statusValue === 'out_of_stock')>Out of Stock</option>
                                <option value="finished" @selected($statusValue === 'finished')>Finished</option>
                            </select>
                            <div class="form-text">Approval and publication remain part of the separate review workflow.</div>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 product-form-field">
                            <label for="shop_category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select id="shop_category_id" name="shop_category_id" class="form-select @error('shop_category_id') is-invalid @enderror" required>
                                <option value="">Select category</option>
                                @foreach($parentCategories as $category)
                                    <option value="{{ $category->id }}" data-subcategories="{{ $category->subcategories->toJson() }}"
                                        @selected(old('shop_category_id', $shopProduct->shop_category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('shop_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 product-form-field">
                            <label for="subcategory_id" class="form-label">Subcategory</label>
                            <select id="subcategory_id" name="subcategory_id" class="form-select @error('subcategory_id') is-invalid @enderror">
                                <option value="">No subcategory</option>
                            </select>
                            @error('subcategory_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 product-form-field">
                            <label for="description" class="form-label">Product Description</label>
                            <textarea id="description" name="description" rows="4" maxlength="10000"
                                      class="form-control @error('description') is-invalid @enderror"
                                      placeholder="Describe the product">{{ old('description', $shopProduct->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 product-form-field">
                            <label for="working_days" class="form-label">Delivery Time (working days) <span class="text-danger">*</span></label>
                            <input id="working_days" name="working_days" type="number" min="1" required
                                   class="form-control @error('working_days') is-invalid @enderror"
                                   value="{{ old('working_days', $shopProduct->working_days ?: 1) }}">
                            @error('working_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </section>

                <section class="product-form-section" aria-labelledby="pricing-heading">
                    <div class="product-form-section-heading"><h2 id="pricing-heading">Pricing and Inventory</h2></div>
                    <div class="row gx-3">
                        <div class="col-md-6 product-form-field">
                            <label for="price" class="form-label">Selling Price (RWF) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">RWF</span>
                                <input id="price" name="price" type="number" min="0" step="0.01" required
                                       class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $shopProduct->price) }}">
                            </div>
                            @error('price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        @if(!$vendorMode || !$editing)
                            <div class="col-md-6 product-form-field">
                                <div class="form-check form-switch product-form-option">
                                    <input class="form-check-input" type="checkbox" role="switch" name="tracks_inventory"
                                           value="1" id="tracks_inventory" @checked($tracksInventory)>
                                    <label class="form-check-label fw-semibold" for="tracks_inventory">Track physical stock</label>
                                </div>
                                <div class="form-text">Leave off for digital services and products without physical inventory.</div>
                            </div>
                        @elseif($shopProduct->tracks_inventory)
                            <div class="col-md-6 product-form-field align-self-center">
                                <div class="form-text">Stock changes are managed through approved inventory operations.</div>
                            </div>
                        @endif
                        @if($editing && $shopProduct->tracks_inventory)
                            <div class="col-md-6 product-form-field">
                                <label class="form-label">Current Stock</label>
                                <div class="form-control bg-light" aria-readonly="true">
                                    {{ number_format($shopProduct->stock_quantity) }} {{ $shopProduct->stock_unit ?: 'units' }}
                                </div>
                                <div class="form-text">Change stock through the Inventory workflow.</div>
                            </div>
                        @endif
                        <div class="col-md-6 product-form-field {{ $inventoryEnabled ? '' : 'd-none' }}" id="stockUnitGroup">
                            <label for="stock_unit" class="form-label">Unit of Measurement</label>
                            <input id="stock_unit" name="stock_unit" maxlength="32"
                                   class="form-control @error('stock_unit') is-invalid @enderror"
                                   value="{{ old('stock_unit', $shopProduct->stock_unit ?? '') }}"
                                   placeholder="e.g. pieces, kg, litre">
                            @error('stock_unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        @unless($editing)
                            <div class="col-md-6 product-form-field {{ $inventoryEnabled ? '' : 'd-none' }}" id="initialStockGroup">
                                <label for="initial_stock_quantity" class="form-label">
                                    Initial Stock Quantity
                                    <span class="text-muted fw-normal">(<span id="initialStockUnit">{{ old('stock_unit', $shopProduct->stock_unit ?? '') ?: 'units' }}</span>)</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <input id="initial_stock_quantity" name="initial_stock_quantity" type="number"
                                       min="0" max="2147483647" step="1" value="{{ old('initial_stock_quantity', 0) }}"
                                       @if($inventoryEnabled) required @else disabled @endif
                                       class="form-control @error('initial_stock_quantity') is-invalid @enderror"
                                       aria-describedby="initialStockHelp">
                                <div id="initialStockHelp" class="form-text">
                                    {{ $vendorMode ? 'This quantity stays pending until an administrator approves the product.' : 'This quantity is registered as an initial stock transaction when the product is approved.' }}
                                </div>
                                @error('initial_stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        @endunless
                        <div class="col-md-6 product-form-field">
                            <div class="form-check form-switch product-form-option">
                                <input class="form-check-input" type="checkbox" role="switch" id="discount_enabled" name="discount_enabled" value="1"
                                       @checked(old('discount_enabled', $shopProduct->discount_enabled))>
                                <label class="form-check-label fw-semibold" for="discount_enabled">Enable Discount</label>
                            </div>
                        </div>
                        <div class="col-md-6 product-form-field {{ old('discount_enabled', $shopProduct->discount_enabled) ? '' : 'd-none' }}" id="originalPriceGroup">
                            <label for="original_price" class="form-label">Original Price (RWF) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">RWF</span>
                                <input id="original_price" name="original_price" type="number" min="0" step="0.01"
                                       class="form-control @error('original_price') is-invalid @enderror"
                                       value="{{ old('original_price', $shopProduct->original_price) }}">
                            </div>
                            <div class="form-text">Must be higher than the selling price.</div>
                            @error('original_price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div id="discountPreview" class="small text-success mt-1" aria-live="polite"></div>
                        </div>
                    </div>
                </section>

                @unless($vendorMode)
                <section class="product-form-section" aria-labelledby="promotion-heading">
                    <div class="product-form-section-heading"><h2 id="promotion-heading">Homepage Promotion</h2></div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_popular" name="is_popular" value="1"
                               @disabled($vendorMode)
                               @checked(old('is_popular', $shopProduct->is_popular))>
                        <label class="form-check-label fw-semibold" for="is_popular">Promote on Homepage</label>
                    </div>
                    <div class="form-text ms-5">{{ $vendorMode ? 'Homepage promotion is managed by an administrator.' : "Include this product in the homepage's promoted products section." }}</div>
                </section>
                @endunless

                <section class="product-form-section" aria-labelledby="images-heading">
                    <div class="product-form-section-heading"><h2 id="images-heading">Product Images</h2></div>
                    <div class="row gx-4 gy-4">
                        <div class="col-md-4">
                            <label class="form-label d-block" for="thumbnail">Main Product Image (Thumbnail)</label>
                            <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="visually-hidden @error('thumbnail') is-invalid @enderror">
                            <input type="hidden" name="remove_thumbnail" id="removeThumbnail" value="{{ $thumbnailRemoved ? '1' : '0' }}">
                            <div id="mainImagePreview" class="product-thumbnail-preview mb-2"
                                 @if(!$shopProduct->thumbnail || $thumbnailRemoved) hidden @endif>
                                @if($shopProduct->thumbnail)
                                    <img src="{{ $assetUrl($shopProduct->thumbnail) }}" alt="Current main product thumbnail" id="currentMainImage">
                                @endif
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <label for="thumbnail" class="btn btn-sm btn-outline-primary mb-0" id="thumbnailChooseButton">
                                    {{ $shopProduct->thumbnail && !$thumbnailRemoved ? 'Replace Image' : 'Choose Image' }}
                                </label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="removeThumbnailButton"
                                        @if(!$shopProduct->thumbnail && !$thumbnailRemoved) hidden @endif>
                                    {{ $thumbnailRemoved ? 'Undo Remove' : 'Remove Image' }}
                                </button>
                            </div>
                            <div id="thumbnailFileName" class="small text-muted mt-2" aria-live="polite"></div>
                            <div class="form-text mt-2">Used on product cards and as the default detail image. JPG, PNG, WebP, or GIF; up to 5 MB.</div>
                            @error('thumbnail')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-8">
                            <label class="form-label d-block">Additional Product Images (Optional)</label>
                            <p class="form-text mt-0 mb-2">Upload more images to show customers different views of this product.</p>
                            <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="visually-hidden">
                            <input type="hidden" name="gallery_order_present" value="1">
                            <button type="button" class="btn btn-sm btn-outline-primary mb-2" id="chooseGalleryImages">
                                <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Images
                            </button>
                            <div id="galleryPreview" class="product-gallery-grid">
                                @foreach($galleryItems as $path)
                                    @php
                                        $decodedPath = base64_decode($path, true);
                                        $imagePath = $decodedPath !== false ? $decodedPath : $path;
                                    @endphp
                                    @if(in_array($imagePath, $shopProduct->images ?? [], true))
                                        <div class="product-gallery-item gallery-item" data-kind="existing" data-path="{{ $imagePath }}">
                                            <input type="hidden" name="gallery_order[]" value="existing:{{ base64_encode($imagePath) }}">
                                            <div class="product-gallery-thumb">
                                                <img src="{{ $assetUrl($imagePath) }}" alt="Additional product image">
                                            </div>
                                            <div class="small gallery-file-name mt-1">{{ basename($imagePath) }}</div>
                                            <div class="product-gallery-actions">
                                                <button type="button" class="btn btn-sm btn-outline-secondary move-image-up" aria-label="Move image earlier" title="Move earlier"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary move-image-down" aria-label="Move image later" title="Move later"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-image" aria-label="Remove {{ basename($imagePath) }}" title="Remove image"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <div id="galleryCount" class="small text-muted mt-2" aria-live="polite"></div>
                            <div id="galleryUploadError" class="text-danger small mt-1" role="alert">
                                @error('images'){{ $message }}@enderror
                                @error('gallery_order'){{ $message }}@enderror
                            </div>
                            <div class="form-text">Up to eight additional images, 5 MB each. Use the arrows to reorder.</div>
                        </div>
                    </div>
                </section>

                <section class="product-form-section" aria-labelledby="specifications-heading">
                    <div class="product-form-section-heading">
                        <h2 id="specifications-heading">Product Specifications and Features</h2>
                    </div>
                    <div class="row gx-4 gy-3">
                        <div class="col-lg-7">
                            <label class="form-label">Specifications</label>
                            <p class="form-text mt-0">Add details such as brand, color, size, model, or material.</p>
                            <div id="specificationsContainer">
                                @forelse($specifications as $index => $specification)
                                    <div class="row gx-2 align-items-start product-repeat-row specification-row">
                                        <div class="col-5">
                                            <input name="specifications[{{ $index }}][label]" class="form-control" maxlength="255"
                                                   value="{{ $specification['label'] ?? '' }}" placeholder="Name" aria-label="Specification name">
                                            @error("specifications.$index.label")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col">
                                            <input name="specifications[{{ $index }}][value]" class="form-control" maxlength="255"
                                                   value="{{ $specification['value'] ?? '' }}" placeholder="Value" aria-label="Specification value">
                                            @error("specifications.$index.value")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger remove-specification" aria-label="Remove specification"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>
                                    </div>
                                @empty
                                    <p class="product-empty-repeat" data-empty="specifications">No specifications added.</p>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addSpecification"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Specification</button>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label">Features</label>
                            <div id="featuresContainer">
                                @forelse($features as $feature)
                                    <div class="input-group input-group-sm product-repeat-row feature-row">
                                        <input name="features[]" class="form-control" maxlength="255" value="{{ $feature }}" placeholder="Product feature" aria-label="Product feature">
                                        <button type="button" class="btn btn-outline-danger remove-feature" aria-label="Remove feature"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                                    </div>
                                @empty
                                    <p class="product-empty-repeat" data-empty="features">No features added.</p>
                                @endforelse
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="addFeature"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Feature</button>
                        </div>
                    </div>
                </section>

                @if(!$vendorMode && auth()->user()->hasPermission('products.assign_vendor'))
                    <section class="product-form-section" aria-labelledby="additional-settings-heading">
                        <div class="product-form-section-heading"><h2 id="additional-settings-heading">Additional Settings</h2></div>
                        @include('shop-products.workflow-fields', ['product' => $shopProduct, 'vendorMode' => $vendorMode])
                    </section>
                @endif

                <div class="product-form-section product-action-bar">
                    <a href="{{ $productListRoute }}" class="btn btn-outline-secondary">Cancel</a>
                    <div class="d-flex align-items-center gap-3 flex-wrap justify-content-end">
                        @if(!$vendorMode && auth()->user()->hasPermission('products.publish') && ($shopProduct->approval_status === 'approved' || (!$editing && auth()->user()->hasPermission('products.approve'))))
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="publish_now" value="1" id="publish_now"
                                       @checked(old('publish_now'))>
                                <label class="form-check-label small fw-semibold" for="publish_now">Publish after saving</label>
                            </div>
                        @endif
                        @if($vendorMode && $editing && in_array($shopProduct->approval_status, ['draft', 'rejected', 'changes_requested'], true))
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" name="submit_for_review" value="1" id="submit_for_review"
                                       @checked(old('submit_for_review'))>
                                <label class="form-check-label small" for="submit_for_review">Submit changes for review</label>
                            </div>
                        @endif
                    <button type="submit" class="btn btn-primary px-4" id="saveProductButton">
                        <span id="saveProductLabel">{{ $editing ? 'Save Changes' : ($vendorMode ? 'Submit Product' : 'Create Product') }}</span>
                        <span id="saveProductSpinner" class="spinner-border spinner-border-sm ms-2" aria-hidden="true" hidden></span>
                    </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('productForm');
    const categorySelect = document.getElementById('shop_category_id');
    const subcategorySelect = document.getElementById('subcategory_id');
    const oldSubcategory = @json(old('subcategory_id', $shopProduct->subcategory_id));
    const discountToggle = document.getElementById('discount_enabled');
    const originalPriceGroup = document.getElementById('originalPriceGroup');
    const priceInput = document.getElementById('price');
    const originalPriceInput = document.getElementById('original_price');
    const discountPreview = document.getElementById('discountPreview');
    const mainInput = document.getElementById('thumbnail');
    const mainPreview = document.getElementById('mainImagePreview');
    const existingThumbnail = document.getElementById('currentMainImage');
    const mainFileName = document.getElementById('thumbnailFileName');
    const removeThumbnailInput = document.getElementById('removeThumbnail');
    const removeThumbnailButton = document.getElementById('removeThumbnailButton');
    const chooseGalleryButton = document.getElementById('chooseGalleryImages');
    const galleryInput = document.getElementById('images');
    const gallery = document.getElementById('galleryPreview');
    const galleryCount = document.getElementById('galleryCount');
    const galleryError = document.getElementById('galleryUploadError');
    const initialStockInput = document.getElementById('initial_stock_quantity');
    const initialStockGroup = document.getElementById('initialStockGroup');
    const stockUnitGroup = document.getElementById('stockUnitGroup');
    const stockUnitInput = document.getElementById('stock_unit');
    const initialStockUnit = document.getElementById('initialStockUnit');
    const trackingInput = document.getElementById('tracks_inventory');
    const originalThumbnailRemoved = removeThumbnailInput.value === '1';
    let uploads = [];
    let uploadId = 0;
    let specIndex = {{ count($specifications) }};
    let thumbnailPreviewUrl = null;

    function loadSubcategories() {
        const option = categorySelect.options[categorySelect.selectedIndex];
        subcategorySelect.innerHTML = '<option value="">No subcategory</option>';
        if (!option || !option.dataset.subcategories) return;
        JSON.parse(option.dataset.subcategories).forEach(function (subcategory) {
            const item = document.createElement('option');
            item.value = subcategory.id;
            item.textContent = subcategory.name;
            if (String(oldSubcategory) === String(subcategory.id)) item.selected = true;
            subcategorySelect.appendChild(item);
        });
    }

    function updateDiscount() {
        const enabled = discountToggle.checked;
        originalPriceGroup.classList.toggle('d-none', !enabled);
        originalPriceInput.required = enabled;
        const price = Number(priceInput.value);
        const original = Number(originalPriceInput.value);
        discountPreview.textContent = enabled && original > price && price >= 0
            ? `${Math.round((1 - price / original) * 100)}% discount`
            : '';
    }

    function updateStockInput() {
        if (!trackingInput) return;
        const enabled = trackingInput.checked;
        if (stockUnitGroup) {
            stockUnitGroup.classList.toggle('d-none', !enabled);
        }
        if (stockUnitInput) {
            stockUnitInput.disabled = !enabled;
        }
        if (initialStockGroup) {
            initialStockGroup.classList.toggle('d-none', !enabled);
        }
        if (initialStockInput) {
            initialStockInput.disabled = !enabled;
            initialStockInput.required = enabled;
        }
        if (initialStockUnit && stockUnitInput) {
            initialStockUnit.textContent = stockUnitInput.value.trim() || 'units';
        }
    }

    function syncUploadInput() {
        const uploadRecords = new Map(uploads.map(record => [record.id, record]));
        const orderedRecords = [];
        const transfer = new DataTransfer();
        gallery.querySelectorAll('[data-kind="upload"]').forEach(function (item, index) {
            const record = uploadRecords.get(item.dataset.uploadId);
            if (!record) return;
            orderedRecords.push(record);
            transfer.items.add(record.file);
            item.querySelector('input[name="gallery_order[]"]').value = `upload:${index}`;
        });
        uploads = orderedRecords;
        galleryInput.files = transfer.files;
    }

    function updateGalleryCount() {
        const count = gallery.querySelectorAll('.gallery-item').length;
        galleryCount.textContent = `${count} of 8 additional images`;
        gallery.querySelectorAll('.gallery-item').forEach(function (item, index, items) {
            item.querySelector('.move-image-up').disabled = index === 0;
            item.querySelector('.move-image-down').disabled = index === items.length - 1;
        });
    }

    function makeUploadTile(record, index) {
        const item = document.createElement('div');
        item.className = 'product-gallery-item gallery-item';
        item.dataset.kind = 'upload';
        item.dataset.uploadId = record.id;
        const url = URL.createObjectURL(record.file);
        item.innerHTML = `
            <input type="hidden" name="gallery_order[]" value="upload:${index}">
            <div class="product-gallery-thumb"><img src="${url}" alt="Selected additional product image"></div>
            <div class="small gallery-file-name mt-1"></div>
            <div class="product-gallery-actions">
                <button type="button" class="btn btn-sm btn-outline-secondary move-image-up" aria-label="Move image earlier" title="Move earlier"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-image-down" aria-label="Move image later" title="Move later"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-image" aria-label="Remove ${record.file.name}" title="Remove image"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>`;
        item.querySelector('.gallery-file-name').textContent = record.file.name;
        return item;
    }

    function renderUploads() {
        gallery.querySelectorAll('[data-kind="upload"]').forEach(function (item) {
            URL.revokeObjectURL(item.querySelector('img').src);
            item.remove();
        });
        uploads.forEach(function (record, index) {
            gallery.appendChild(makeUploadTile(record, index));
        });
        syncUploadInput();
        updateGalleryCount();
    }

    function renderThumbnail() {
        if (thumbnailPreviewUrl) {
            URL.revokeObjectURL(thumbnailPreviewUrl);
            thumbnailPreviewUrl = null;
        }
        const selectedFile = mainInput.files[0];
        if (selectedFile) {
            const image = document.createElement('img');
            thumbnailPreviewUrl = URL.createObjectURL(selectedFile);
            image.src = thumbnailPreviewUrl;
            image.alt = 'Selected main product thumbnail preview';
            mainPreview.replaceChildren(image);
            mainPreview.hidden = false;
            mainFileName.textContent = selectedFile.name;
            removeThumbnailButton.hidden = false;
            removeThumbnailButton.textContent = 'Remove Image';
            removeThumbnailButton.setAttribute('aria-label', 'Remove selected main image');
            removeThumbnailInput.value = originalThumbnailRemoved ? '1' : '0';
        } else if (existingThumbnail && removeThumbnailInput.value !== '1') {
            mainPreview.replaceChildren(existingThumbnail);
            mainPreview.hidden = false;
            mainFileName.textContent = '';
            removeThumbnailButton.hidden = false;
            removeThumbnailButton.textContent = 'Remove Image';
            removeThumbnailButton.setAttribute('aria-label', 'Remove current main image');
        } else {
            mainPreview.replaceChildren();
            mainPreview.hidden = true;
            mainFileName.textContent = '';
            removeThumbnailButton.hidden = !existingThumbnail;
            removeThumbnailButton.textContent = 'Undo Remove';
            removeThumbnailButton.setAttribute('aria-label', 'Undo removing the current main image');
        }
        document.getElementById('thumbnailChooseButton').textContent =
            (selectedFile || (existingThumbnail && removeThumbnailInput.value !== '1')) ? 'Replace Image' : 'Choose Image';
    }

    categorySelect.addEventListener('change', function () {
        subcategorySelect.value = '';
        loadSubcategories();
    });
    loadSubcategories();
    discountToggle.addEventListener('change', updateDiscount);
    priceInput.addEventListener('input', updateDiscount);
    originalPriceInput.addEventListener('input', updateDiscount);
    updateDiscount();
    if (trackingInput) {
        trackingInput.addEventListener('change', updateStockInput);
    }
    if (stockUnitInput) {
        stockUnitInput.addEventListener('input', updateStockInput);
    }
    updateStockInput();

    mainInput.addEventListener('change', renderThumbnail);
    removeThumbnailButton.addEventListener('click', function () {
        if (mainInput.files.length) {
            mainInput.value = '';
            removeThumbnailInput.value = originalThumbnailRemoved ? '1' : '0';
        } else if (removeThumbnailInput.value === '1') {
            removeThumbnailInput.value = '0';
        } else {
            removeThumbnailInput.value = '1';
        }
        renderThumbnail();
    });
    chooseGalleryButton.addEventListener('click', function () {
        galleryInput.click();
    });
    galleryInput.addEventListener('change', function () {
        const selected = Array.from(this.files || []);
        const existingCount = gallery.querySelectorAll('[data-kind="existing"]').length;
        if (selected.length + uploads.length + existingCount > 8) {
            galleryError.textContent = `Only ${Math.max(0, 8 - existingCount - uploads.length)} more additional image(s) can be added.`;
            galleryInput.value = '';
            return;
        }
        galleryError.textContent = '';
        uploads = uploads.concat(selected.map(file => ({ id: String(uploadId++), file })));
        renderUploads();
    });
    gallery.addEventListener('click', function (event) {
        const button = event.target.closest('button');
        if (!button) return;
        const item = button.closest('.gallery-item');
        if (!item) return;
        if (button.classList.contains('remove-image')) {
            if (item.dataset.kind === 'upload') {
                URL.revokeObjectURL(item.querySelector('img').src);
                uploads = uploads.filter(upload => upload.id !== item.dataset.uploadId);
            }
            item.remove();
            syncUploadInput();
            updateGalleryCount();
            return;
        }
        const sibling = button.classList.contains('move-image-up') ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling || !sibling.classList.contains('gallery-item')) return;
        if (button.classList.contains('move-image-up')) gallery.insertBefore(item, sibling);
        else gallery.insertBefore(sibling, item);
        syncUploadInput();
        updateGalleryCount();
    });
    updateGalleryCount();
    renderThumbnail();

    document.getElementById('addSpecification').addEventListener('click', function () {
        const emptyMessage = document.querySelector('[data-empty="specifications"]');
        if (emptyMessage) emptyMessage.remove();
        const row = document.createElement('div');
        row.className = 'row gx-2 align-items-center product-repeat-row specification-row';
        row.innerHTML = `
            <div class="col-5"><input name="specifications[${specIndex}][label]" class="form-control" maxlength="255" placeholder="Name" aria-label="Specification name"></div>
            <div class="col"><input name="specifications[${specIndex}][value]" class="form-control" maxlength="255" placeholder="Value" aria-label="Specification value"></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-danger remove-specification" aria-label="Remove specification"><i class="bi bi-x-lg" aria-hidden="true"></i></button></div>`;
        specIndex += 1;
        document.getElementById('specificationsContainer').appendChild(row);
        row.querySelector('input').focus();
    });
    document.getElementById('addFeature').addEventListener('click', function () {
        const emptyMessage = document.querySelector('[data-empty="features"]');
        if (emptyMessage) emptyMessage.remove();
        const row = document.createElement('div');
        row.className = 'input-group input-group-sm product-repeat-row feature-row';
        row.innerHTML = '<input name="features[]" class="form-control" maxlength="255" placeholder="Product feature" aria-label="Product feature"><button type="button" class="btn btn-outline-danger remove-feature" aria-label="Remove feature"><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
        document.getElementById('featuresContainer').appendChild(row);
        row.querySelector('input').focus();
    });
    document.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-specification, .remove-feature');
        if (!button) return;
        const row = button.closest('.specification-row, .feature-row');
        const container = row.parentElement;
        row.remove();
        if (!container.querySelector('.specification-row, .feature-row')) {
            const kind = container.id === 'specificationsContainer' ? 'specifications' : 'features';
            const empty = document.createElement('p');
            empty.className = 'product-empty-repeat';
            empty.dataset.empty = kind;
            empty.textContent = kind === 'features' ? 'No features added.' : 'No specifications added.';
            container.appendChild(empty);
        }
    });

    form.addEventListener('submit', function () {
        const saveButton = document.getElementById('saveProductButton');
        saveButton.disabled = true;
        document.getElementById('saveProductLabel').textContent = 'Saving...';
        document.getElementById('saveProductSpinner').hidden = false;
    });
});
</script>
