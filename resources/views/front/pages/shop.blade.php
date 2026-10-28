@extends('front.layouts.guest')
@section('content')

{{--
    VARIABLES expected from ShopProductController:
    - $parentCategories : Collection of parent ShopCategory (with ->subcategories loaded)
    - $products         : Collection of ShopProduct
                          each product:  ->category   = parent  (shop_category_id)
                                         ->subcategory = subcat  (subcategory_id)
    - $activeType       : slug of active parent tab  (default 'all')
    - $activeSubcat     : slug of active subcategory (default 'all')
--}}

<main class="py-12 py-md-20">
    <div class="container">

        {{-- ── Header ──────────────────────────────────────────────────────── --}}
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold mb-4">Shop & Order</h1>
            <p class="fs-5 text-muted mx-auto" style="max-width: 600px;">
                Physical products, digital services, and tech accessories —
                delivered to your door or activated instantly.
            </p>
        </div>

        @php
            $activeType   = $activeType   ?? request('category',    'all');
            $activeSubcat = $activeSubcat ?? request('subcategory', 'all');

            // Parent category whose slug matches the active tab
            $activeParentCat = $activeType === 'all'
                ? null
                : $parentCategories->firstWhere('slug', $activeType);

            // Build tabs: [All] + each parent category
            // $product->category = parent (shop_category_id) — count by parent slug
            $totalCount = $allProductsCount;
            $tabs = collect([(object)[
                'slug'  => 'all',
                'name'  => 'All Products',
                'count' => $totalCount,
            ]]);

            foreach ($parentCategories as $parent) {
                $count = $categoryCounts->get($parent->id, 0);

                $tabs->push((object)[
                    'slug'  => $parent->slug,
                    'name'  => $parent->name,
                    'count' => $count,
                ]);
            }
        @endphp

        {{-- ── Type Tabs (dynamic) ─────────────────────────────────────────── --}}
        <div class="d-flex justify-content-center gap-3 flex-wrap mt-4 mb-4">
            @foreach($tabs as $tab)
                <a href="{{ route('shop', ['category' => $tab->slug, 'subcategory' => 'all']) }}"
                   class="tab-btn d-flex align-items-center gap-2
                          {{ $activeType === $tab->slug ? 'tab-btn-active' : 'tab-btn-inactive' }}"
                   style="border-radius:9999px; padding:0.625rem 1.25rem;
                          font-size:0.875rem; text-decoration:none; transition:all 0.2s ease;">

                    {{-- Optional icon per parent slug --}}
                    @if($tab->slug === 'all')
                        <i class="bi bi-grid-3x3-gap-fill" style="font-size:0.85rem;"></i>
                    @elseif($tab->slug === 'physical')
                        <i class="bi bi-box-seam" style="font-size:0.85rem;"></i>
                    @elseif($tab->slug === 'digital')
                        <i class="bi bi-lightning-charge-fill" style="font-size:0.85rem;"></i>
                    @else
                        <i class="bi bi-tag" style="font-size:0.85rem;"></i>
                    @endif

                    <span>{{ $tab->name }}</span>
                    <span class="{{ $activeType === $tab->slug ? 'badge-count-active' : 'badge-count-inactive' }}"
                          style="font-size:0.7rem; border-radius:9999px;
                                 padding:0.15rem 0.5rem; font-weight:600;">
                        {{ $tab->count }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- ── Search ───────────────────────────────────────────────────────── --}}
        <div class="row justify-content-center mb-4">
            <div class="col-12 col-md-6" style="max-width:500px;">
                <div class="input-group input-group-lg"
                     style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text"
                           id="liveSearch"
                           class="form-control border-0"
                           placeholder="Search products..."
                           style="font-size:0.875rem; box-shadow:none;"
                           autocomplete="off">
                    <span class="input-group-text bg-white border-0"
                          id="shopClearSearch" style="cursor:pointer; display:none;">
                        <i class="bi bi-x-circle text-muted"></i>
                    </span>
                </div>
                <div id="searchResultCount"
                     class="text-muted mt-2 text-center"
                     style="font-size:0.8rem; display:none;"></div>
            </div>
        </div>

        {{-- ── Subcategory Pills (dynamic) ─────────────────────────────────── --}}
        @if($activeParentCat && $activeParentCat->subcategories->count() > 0)
            <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
                {{-- "All <ParentName>" pill --}}
                <a href="{{ route('shop', ['category' => $activeType, 'subcategory' => 'all']) }}"
                   class="subcat-pill {{ $activeSubcat === 'all' ? 'subcat-pill-active' : 'subcat-pill-inactive' }}"
                   style="text-decoration:none; transition:all 0.2s ease;">
                    All {{ $activeParentCat->name }}
                </a>

                @foreach($activeParentCat->subcategories as $sub)
                    <a href="{{ route('shop', ['category' => $activeType, 'subcategory' => $sub->slug]) }}"
                       class="subcat-pill {{ $activeSubcat === $sub->slug ? 'subcat-pill-active' : 'subcat-pill-inactive' }}"
                       style="text-decoration:none; transition:all 0.2s ease;">
                        {{ $sub->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ── Products Grid ────────────────────────────────────────────────── --}}
        @if($products->isEmpty())
            <div class="text-center py-5 mt-4">
                <div style="font-size:3rem; margin-bottom:1rem;">📦</div>
                <p class="text-muted mb-3">No products found matching your criteria.</p>
                <a href="{{ route('shop') }}"
                   class="btn btn-sm"
                   style="border:1px solid #d1d5db; color:#374151;
                          border-radius:8px; padding:0.5rem 1.2rem;">
                    Clear Filters
                </a>
            </div>
        @else
            <div id="noSearchResults" class="text-center py-5 mt-4" style="display:none;">
                <div style="font-size:3rem; margin-bottom:1rem;">🔍</div>
                <p class="text-muted mb-3">No products match your search.</p>
                <button onclick="clearSearch()"
                        class="btn btn-sm"
                        style="border:1px solid #d1d5db; color:#374151;
                               border-radius:8px; padding:0.5rem 1.2rem;">
                    Clear Search
                </button>
            </div>

            <div class="row g-4 mt-2" id="productsGrid">
                @foreach($products as $product)
                    @php
                        // From controller:
                        // $product->category    = parent category (shop_category_id)
                        // $product->subcategory = subcategory     (subcategory_id)
                        $productParent = $product->category;
                        $productSubcat = $product->subcategory;
                    @endphp
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 product-item"
                         data-name="{{ strtolower($product->product_name) }}"
                         data-description="{{ strtolower($product->description ?? '') }}"
                         data-parent="{{ strtolower($productParent?->name ?? '') }}"
                         data-category="{{ strtolower($productSubcat?->name ?? '') }}"
                         data-type="{{ strtolower($productParent?->slug ?? '') }}">

                        <a href="{{ route('shop-products.show', $product) }}" class="text-decoration-none">
                            <div class="product-card h-100">

                                {{-- Image --}}
                                <div class="product-img-wrap">
                                    @if($product->thumbnail)
                                        <img src="{{ asset('assets/' . $product->thumbnail) }}"
                                             alt="{{ $product->product_name }}"
                                             class="product-img">
                                    @else
                                        <div class="product-img-placeholder">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    @endif
                                </div>

                                <div class="product-card-body d-flex flex-column">

                                    {{-- Badges: parent type + stock status --}}
                                    <div class="d-flex gap-2 mb-2 flex-wrap">
                                        @if($productParent)
                                            @php
                                                $typeColors = [
                                                    'physical' => ['bg'=>'#f0fdf4','color'=>'#15803d','border'=>'#bbf7d0'],
                                                    'digital'  => ['bg'=>'#eff6ff','color'=>'#1d4ed8','border'=>'#bfdbfe'],
                                                ];
                                                $tc = $typeColors[$productParent->slug]
                                                   ?? ['bg'=>'#f3f4f6','color'=>'#374151','border'=>'#e5e7eb'];
                                            @endphp
                                            <span class="product-badge"
                                                  style="background-color:{{ $tc['bg'] }};
                                                         color:{{ $tc['color'] }};
                                                         border-color:{{ $tc['border'] }};">
                                                {{ $productParent->name }}
                                            </span>
                                        @endif

                                        <span class="product-badge"
                                              style="background-color:{{ $product->status === 'in_stock' ? '#dcfce7' : '#fee2e2' }};
                                                     color:{{ $product->status === 'in_stock' ? '#15803d' : '#b91c1c' }};
                                                     border-color:{{ $product->status === 'in_stock' ? '#bbf7d0' : '#fecaca' }};">
                                            {{ $product->status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                                        </span>
                                    </div>

                                    {{-- Breadcrumb: Parent › Subcategory --}}
                                    @if($productParent || $productSubcat)
                                        <p class="mb-1"
                                           style="font-size:0.72rem; color:#9ca3af; letter-spacing:0.3px;">
                                            {{ $productParent?->name ?? '' }}
                                            @if($productSubcat)
                                                <span style="color:#d1d5db;"> › </span>
                                                {{ $productSubcat->name }}
                                            @endif
                                        </p>
                                    @endif

                                    {{-- Name --}}
                                    <h5 class="fw-bold mb-2 product-title"
                                        style="font-size:0.95rem; color:#111827; line-height:1.4;">
                                        {{ $product->product_name }}
                                    </h5>

                                    {{-- Description --}}
                                    @if($product->description)
                                        <p class="mb-3 flex-grow-1"
                                           style="font-size:0.82rem; color:#6b7280; line-height:1.5;
                                                  display:-webkit-box; -webkit-line-clamp:2;
                                                  -webkit-box-orient:vertical; overflow:hidden;">
                                            {{ $product->description }}
                                        </p>
                                    @endif

                                    {{-- Price + Order button --}}
                                    <div class="mt-auto pt-3 d-flex align-items-center justify-content-between"
                                         style="border-top:1px solid #f3f4f6;">
                                        <div>
                                            <p class="fw-bold mb-0"
                                               style="font-size:1rem; color:#15803d;">
                                                RWF {{ number_format($product->price, 0, '.', ',') }}
                                            </p>
                                            @if($product->working_days)
                                                <p class="mb-0"
                                                   style="font-size:0.75rem; color:#9ca3af;">
                                                    {{ $product->working_days }} working day{{ $product->working_days != 1 ? 's' : '' }}
                                                </p>
                                            @endif
                                        </div>
                                        <span class="d-flex align-items-center gap-1"
                                              style="background-color:#15803d; color:white;
                                                     font-size:0.82rem; font-weight:600;
                                                     padding:0.45rem 0.9rem; border-radius:8px;">
                                            <i class="bi bi-whatsapp"></i> Order
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ── CTA ─────────────────────────────────────────────────────────── --}}
        <div class="mt-5 p-5 text-center rounded-3"
             style="background-color:#f9fafb; border:1px solid #e5e7eb;">
            <h4 class="fw-bold mb-2">Can't Find What You Need?</h4>
            <p class="text-muted mb-4">
                We can source any product for you through our Assisted Online Purchase service.
            </p>
            <a href="{{ route('request-service') }}"
               class="btn d-inline-flex align-items-center gap-2"
               style="background-color:#15803d; color:white; border:none;
                      padding:0.65rem 1.5rem; border-radius:8px; font-weight:500;">
                <i class="bi bi-question-circle"></i> Request a Product
            </a>
        </div>

    </div>
</main>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
<style>
.py-md-20 { padding-top:5rem; padding-bottom:5rem; }
@media (max-width:768px) { .py-md-20 { padding-top:3rem; padding-bottom:3rem; } }

/* ── Tabs ── */
.tab-btn { font-weight:500; }
.tab-btn-active  { background-color:#15803d; color:white !important; border:1px solid #15803d; font-weight:600; }
.tab-btn-inactive { background-color:white; color:#374151; border:1px solid #e5e7eb; }
.tab-btn-inactive:hover { background-color:#f0fdf4; border-color:#15803d; color:#15803d; }
.badge-count-active  { background-color:white; color:#15803d; }
.badge-count-inactive { background-color:#f3f4f6; color:#6b7280; }

/* ── Subcategory pills ── */
.subcat-pill { display:inline-block; padding:0.375rem 1rem; border-radius:9999px; font-size:0.875rem; border:1px solid; }
.subcat-pill-active   { background-color:#111827; color:white !important; border-color:#111827 !important; font-weight:600; }
.subcat-pill-inactive { background-color:white; color:#374151; border-color:#e5e7eb !important; }
.subcat-pill-inactive:hover { background-color:#f9fafb; border-color:#111827 !important; color:#111827; }

/* ── Product card ── */
.product-card {
    background:white; border:1px solid #e5e7eb; border-radius:12px;
    overflow:hidden; transition:all 0.3s cubic-bezier(.4,0,.2,1);
    display:flex; flex-direction:column;
}
.product-card:hover { box-shadow:0 12px 32px rgba(0,0,0,.10); transform:translateY(-4px); }
.product-card-body  { padding:1rem; flex-grow:1; display:flex; flex-direction:column; }
.product-img-wrap   { width:100%; height:180px; overflow:hidden; background:#f3f4f6; }
.product-img        { width:100%; height:100%; object-fit:cover; transition:transform 0.3s ease; }
.product-card:hover .product-img { transform:scale(1.05); }
.product-img-placeholder {
    width:100%; height:100%; display:flex;
    align-items:center; justify-content:center;
    font-size:3rem; color:#9ca3af;
}
.product-badge {
    display:inline-block; padding:0.2rem 0.55rem;
    font-size:0.7rem; font-weight:500;
    border-radius:9999px; border:1px solid;
}
.search-highlight { background-color:#fef08a; border-radius:2px; padding:0 1px; }
.product-item { transition:opacity 0.2s ease, transform 0.2s ease; }
.product-item.hidden { display:none !important; }
</style>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
<script>
const searchInput  = document.getElementById('liveSearch');
const clearBtn     = document.getElementById('shopClearSearch');
const noResults    = document.getElementById('noSearchResults');
const resultCount  = document.getElementById('searchResultCount');
const productItems = document.querySelectorAll('.product-item');

function highlightText(element, query) {
    element.querySelectorAll('.product-title').forEach(el => {
        const original = el.getAttribute('data-original') || el.textContent;
        el.setAttribute('data-original', original);
        if (!query) { el.innerHTML = original; return; }
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');
        el.innerHTML = original.replace(regex, '<mark class="search-highlight">$1</mark>');
    });
}

function clearSearch() {
    searchInput.value = '';
    searchInput.dispatchEvent(new Event('input'));
    searchInput.focus();
}

searchInput.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    let visible = 0;

    clearBtn.style.display    = query ? 'flex'  : 'none';
    resultCount.style.display = query ? 'block' : 'none';

    productItems.forEach(item => {
        const matches = !query
            || (item.dataset.name        || '').includes(query)
            || (item.dataset.description || '').includes(query)
            || (item.dataset.parent      || '').includes(query)
            || (item.dataset.category    || '').includes(query)
            || (item.dataset.type        || '').includes(query);

        item.classList.toggle('hidden', !matches);
        if (matches) { highlightText(item, query); visible++; }
    });

    if (noResults)    noResults.style.display    = visible === 0 ? 'block' : 'none';
    if (resultCount)  resultCount.textContent    = `${visible} product${visible !== 1 ? 's' : ''} found`;
});

clearBtn.addEventListener('click', clearSearch);
</script>

@endsection