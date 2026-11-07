@extends('front.layouts.guest')
@section('content')
@php
    $productGallery = collect(array_merge([$shopProduct->thumbnail], $shopProduct->images ?? []))
        ->filter(fn ($path) => is_string($path) && trim($path) !== '')
        ->map(fn ($path) => preg_replace('#^(public/)?assets/#', '', ltrim($path, '/')))
        ->filter(fn ($path) => $path !== '' && ! str_contains($path, '..') && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$path)))
        ->unique()
        ->values()
        ->all();
    $productGalleryUrl = fn ($path) => asset('assets/' . $path);
    $productVariations = $shopProduct->variations->filter(fn ($variation) =>
        filled($variation->feature_label)
        && filled($variation->variation_value)
        && is_numeric($variation->price)
        && (float) $variation->price >= 0
    )->values();
    $selectedProductVariation = $productVariations->firstWhere('is_default', true) ?? $productVariations->first();
    $initialVariation = $selectedProductVariation;
    $initialPurchasePrice = $initialVariation ? (float) $initialVariation->price : (float) $shopProduct->price;
    $isInStock = $shopProduct->customerAvailability() === 'in_stock';
    $purchaseMaxQuantity = max(1, (int) ($shopProduct->tracks_inventory ? $shopProduct->availableQuantity() : 100));
    $money = fn ($value) => 'RWF ' . number_format((float) $value, 0, '.', ',');
    $productUrl = route('shop-products.show', $shopProduct);

    // Specifications as label/value pairs, for the "Key attributes" blocks.
    $attributes = collect($shopProduct->specifications ?? [])
        ->map(fn ($spec) => is_array($spec)
            ? ['label' => (string) ($spec['label'] ?? ''), 'value' => (string) ($spec['value'] ?? '')]
            : ['label' => (string) $spec, 'value' => ''])
        ->filter(fn ($spec) => trim($spec['label']) !== '')
        ->values();
    $baseAttributes = collect([
        ['label' => 'Category', 'value' => $shopProduct->category?->name],
        ['label' => 'Type', 'value' => $shopProduct->subcategory?->name],
        ['label' => 'Model Number', 'value' => $shopProduct->sku],
        ['label' => 'Availability', 'value' => $isInStock ? 'In stock' : 'Out of stock'],
    ])->filter(fn ($attribute) => filled($attribute['value']));
    $allAttributes = $baseAttributes->concat($attributes)->values();

    $whatsappBase = 'https://wa.me/250780290040';
    $initialVariationName = $initialVariation?->variation_value ? ' — ' . $initialVariation->variation_value : '';
    $purchaseWhatsappUrl = $whatsappBase . '?text=' . urlencode(
        "Hi TRESCA Hub! I'm interested in: *" . $shopProduct->product_name . $initialVariationName . "*\n" .
        "Quantity: 1\n" .
        "Price: " . $money($initialPurchasePrice) . "\n" .
        "Please share more details.\n\n" .
        "Product Link: " . $productUrl
    );
@endphp

<main class="pd-page">
    <div class="container pd-container">

        {{-- Breadcrumb --}}
        <nav class="pd-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ url('/') }}">Home</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('shop') }}">Shop</a>
            @if($shopProduct->category)
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span>{{ $shopProduct->category->name }}</span>
            @endif
            @if($shopProduct->subcategory)
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                <span>{{ $shopProduct->subcategory->name }}</span>
            @endif
        </nav>

        <div class="pd-top">
            {{-- ================= Left: gallery + seller ================= --}}
            <div class="pd-gallery-col">
                <div class="pd-gallery">
                    @if(count($productGallery) > 0)
                        <div class="pd-thumbs" id="productGallery">
                            @foreach($productGallery as $index => $image)
                                <button type="button" class="pd-thumb gallery-thumbnail {{ $index === 0 ? 'is-active' : '' }}"
                                        data-gallery-index="{{ $index }}"
                                        data-gallery-src="{{ $productGalleryUrl($image) }}"
                                        aria-label="View product image {{ $index + 1 }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                    <img src="{{ $productGalleryUrl($image) }}" alt="Product image {{ $index + 1 }}" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="pd-main-image">
                        @if(count($productGallery) > 0)
                            <img id="productMainImage" src="{{ $productGalleryUrl($productGallery[0]) }}"
                                 alt="{{ $shopProduct->product_name }}"
                                 data-bs-toggle="modal" data-bs-target="#imageModal">
                            @if(count($productGallery) > 1)
                                <button type="button" class="pd-arrow is-prev" data-gallery-step="-1" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" class="pd-arrow is-next" data-gallery-step="1" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
                                <span class="pd-counter"><span id="galleryCounter">1</span> / {{ count($productGallery) }}</span>
                            @endif
                            <button type="button" class="pd-zoom" data-bs-toggle="modal" data-bs-target="#imageModal" aria-label="Enlarge image"><i class="bi bi-arrows-fullscreen"></i></button>
                        @else
                            <div class="pd-no-image"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                        @endif
                    </div>
                </div>

                {{-- Seller --}}
                <div class="pd-seller">
                    <div class="pd-seller-logo">TH</div>
                    <div class="pd-seller-info">
                        <div class="pd-seller-name">TRESCA Hub Marketplace</div>
                        <div class="pd-seller-meta"><i class="bi bi-geo-alt"></i> Kigali, Rwanda</div>
                    </div>
                    <div class="pd-seller-badges">
                        <span><i class="bi bi-patch-check-fill"></i> Verified products</span>
                        <span><i class="bi bi-truck"></i> Local delivery</span>
                    </div>
                </div>
            </div>

            {{-- ================= Middle: product info ================= --}}
            <div class="pd-info-col">
                <h1 class="pd-title">{{ $shopProduct->product_name }}</h1>
                <div class="pd-subline">
                    <a href="#reviews" class="pd-rating-link">
                        @include('front.pages.partials.rating-stars', ['rating' => $reviewSummary['average'], 'size' => '.9rem'])
                        @if($reviewSummary['count'] > 0)
                            <strong>{{ number_format($reviewSummary['average'], 1) }}</strong>
                            <span>({{ $reviewSummary['count'] }} {{ \Illuminate\Support\Str::plural('review', $reviewSummary['count']) }})</span>
                        @else
                            <span>No reviews yet</span>
                        @endif
                    </a>
                    @if($shopProduct->category)<span>{{ $shopProduct->category->name }}</span>@endif
                    <span class="{{ $isInStock ? 'text-success' : 'text-danger' }}">
                        <i class="bi bi-circle-fill" style="font-size:.5rem"></i> {{ $isInStock ? 'In Stock' : 'Out of Stock' }}
                    </span>
                    @if($shopProduct->sku)<span>SKU: {{ $shopProduct->sku }}</span>@endif
                </div>

                {{-- Price block --}}
                <div class="pd-price-box">
                    @if($productVariations->count() > 1)
                        <div class="pd-price-tiers">
                            @foreach($productVariations->take(4) as $variation)
                                <div class="pd-price-tier">
                                    <div class="pd-tier-price">{{ $money($variation->price) }}</div>
                                    <div class="pd-tier-label">{{ $variation->variation_value }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div class="pd-price-line">
                        <span id="selectedProductPrice" class="pd-price">{{ $money($initialPurchasePrice) }}</span>
                        @if($shopProduct->hasValidDiscount())
                            <span class="pd-old-price">{{ $money($shopProduct->original_price) }}</span>
                            <span class="pd-discount">{{ $shopProduct->discount_percentage }}% off</span>
                        @endif
                    </div>
                </div>

                {{-- Options --}}
                @if($productVariations->isNotEmpty())
                    <fieldset id="productVariationSelector" class="pd-block">
                        <legend class="pd-block-title">Choose your preferred option
                            <span class="pd-block-hint">{{ $selectedProductVariation->feature_label }}:
                                <strong id="selectedVariationName">{{ $selectedProductVariation->variation_value }}</strong>
                            </span>
                        </legend>
                        <div class="pd-options">
                            @foreach($productVariations as $variation)
                                <label class="pd-option product-variation-option {{ $variation->id === $selectedProductVariation->id ? 'is-selected' : '' }}">
                                    <input
                                        type="radio"
                                        name="product_variation_choice"
                                        value="{{ $variation->id }}"
                                        data-product-variation-choice
                                        data-variation-price="{{ $variation->price }}"
                                        data-variation-name="{{ e($variation->variation_value) }}"
                                        @checked($variation->id === $selectedProductVariation->id)
                                        aria-label="{{ $variation->feature_label }}: {{ $variation->variation_value }}, {{ $money($variation->price) }}"
                                    >
                                    <span class="pd-option-value">{{ $variation->variation_value }}</span>
                                    <span class="pd-option-price">{{ $money($variation->price) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                {{-- Key attributes (short) --}}
                @if($allAttributes->isNotEmpty())
                    <div class="pd-block">
                        <div class="pd-block-title">Key attributes</div>
                        <dl class="pd-key-grid">
                            @foreach($allAttributes->take(6) as $attribute)
                                <div>
                                    <dt>{{ $attribute['label'] }}</dt>
                                    <dd>{{ $attribute['value'] !== '' ? $attribute['value'] : '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        @if($allAttributes->count() > 6)
                            <a href="#attributes" class="pd-link">View all attributes <i class="bi bi-chevron-down"></i></a>
                        @endif
                    </div>
                @endif

                @if($shopProduct->description)
                    <p class="pd-short-description">{{ \Illuminate\Support\Str::limit($shopProduct->description, 220) }}</p>
                @endif
            </div>

            {{-- ================= Right: order card ================= --}}
            <aside class="pd-order-col">
                <div class="pd-order-card">
                    <div class="pd-order-section">
                        <div class="pd-order-heading">Delivery</div>
                        <p class="pd-order-text"><i class="bi bi-truck"></i> Estimated in <strong>{{ $shopProduct->working_days }} working day(s)</strong> within Rwanda.</p>
                    </div>

                    <div class="pd-order-section">
                        <div class="pd-order-heading">Quantity</div>
                        <div class="purchase-quantity" role="group" aria-label="Quantity to order">
                            <button type="button" id="purchaseQuantityMinus" class="purchase-quantity-button" aria-label="Decrease quantity"><i class="bi bi-dash-lg"></i></button>
                            <input id="purchaseQuantity" form="addToCartForm" type="number" name="quantity" value="1" min="1"
                                   max="{{ $purchaseMaxQuantity }}" class="purchase-quantity-value" aria-label="Quantity">
                            <button type="button" id="purchaseQuantityPlus" class="purchase-quantity-button" aria-label="Increase quantity"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <div class="pd-order-total">Total: <strong id="purchaseTotal">{{ $money($initialPurchasePrice) }}</strong></div>
                    </div>

                    <div class="pd-order-section">
                        <div class="pd-order-heading d-flex justify-content-between">
                            <span>TRESCA Hub protection</span>
                        </div>
                        <ul class="pd-protection">
                            <li>
                                <i class="bi bi-shield-check"></i>
                                <div>
                                    <strong>Secure payments</strong>
                                    <span class="pd-pay-logos">
                                        <img src="{{ asset('assets/payment-providers/mtn-momo.png') }}" alt="MTN MoMo" height="18">
                                        <img src="{{ asset('assets/payment-providers/airtel-money.svg') }}" alt="Airtel Money" height="18">
                                    </span>
                                    <small>Pay with Mobile Money and get a payment confirmation instantly.</small>
                                </div>
                            </li>
                            <li>
                                <i class="bi bi-box-seam"></i>
                                <div>
                                    <strong>Order tracking</strong>
                                    <small>Follow your order from confirmation to delivery in your account.</small>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="pd-order-actions">
                        <form id="addToCartForm" method="POST" action="{{ route('cart.add') }}">
                            @csrf
                            <input type="hidden" name="product" value="{{ $shopProduct->slug }}">
                            @if($productVariations->isNotEmpty())
                                <input id="selectedVariationId" type="hidden" name="variation_id" value="{{ $selectedProductVariation->id }}">
                            @endif
                            <button class="pd-btn pd-btn-primary" type="submit" @disabled(! $isInStock)>
                                <i class="bi bi-cart3"></i> Add to Cart
                            </button>
                        </form>
                        <a id="requestProductButton"
                           href="{{ route('checkout', ['product' => $shopProduct->slug, 'quantity' => 1] + ($selectedProductVariation ? ['variation_id' => $selectedProductVariation->id] : [])) }}"
                           class="pd-btn pd-btn-outline"><i class="bi bi-lightning-charge"></i> Buy Now</a>
                        <a id="purchaseWhatsappButton"
                           href="{{ $purchaseWhatsappUrl }}" target="_blank" rel="noopener"
                           class="pd-btn pd-btn-whatsapp"
                           data-whatsapp-base="{{ $whatsappBase }}"
                           data-product-name="{{ e($shopProduct->product_name) }}"
                           data-product-url="{{ $productUrl }}"><i class="bi bi-whatsapp"></i> Chat on WhatsApp</a>
                    </div>

                    @if(! $isInStock)
                        <div class="alert alert-warning small mt-3 mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> This product is currently out of stock. Contact us for availability.
                        </div>
                    @endif
                </div>
            </aside>
        </div>

        {{-- ================= Recommendations ================= --}}
        @if(isset($related) && $related->count() > 0)
            <section class="pd-section pd-reco">
                <div class="pd-reco-head">
                    <h2 class="pd-section-title">Other recommendations for you</h2>
                    <div class="pd-reco-nav">
                        <button type="button" data-reco-scroll="-1" aria-label="Scroll left"><i class="bi bi-chevron-left"></i></button>
                        <button type="button" data-reco-scroll="1" aria-label="Scroll right"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
                <div class="pd-reco-track" id="recoTrack">
                    @foreach($related as $relatedProduct)
                        @php
                            $relatedThumb = $relatedProduct->thumbnail
                                ? asset('assets/' . ltrim(str_replace('public/assets/', '', $relatedProduct->thumbnail), '/'))
                                : null;
                        @endphp
                        <a href="{{ route('shop-products.show', $relatedProduct) }}" class="pd-reco-card">
                            <div class="pd-reco-image">
                                @if($relatedThumb)
                                    <img src="{{ $relatedThumb }}" alt="{{ $relatedProduct->product_name }}" loading="lazy">
                                @else
                                    <i class="bi bi-box-seam"></i>
                                @endif
                                @if($relatedProduct->hasValidDiscount())
                                    <span class="pd-reco-badge">{{ $relatedProduct->discount_percentage }}% off</span>
                                @endif
                            </div>
                            <div class="pd-reco-name">{{ $relatedProduct->product_name }}</div>
                            @if(($relatedProduct->rating_count ?? 0) > 0)
                                <div class="pd-reco-rating">
                                    @include('front.pages.partials.rating-stars', ['rating' => $relatedProduct->rating_average, 'size' => '.75rem'])
                                    <span>({{ $relatedProduct->rating_count }})</span>
                                </div>
                            @endif
                            <div class="pd-reco-price">{{ $money($relatedProduct->price) }}</div>
                            @if($relatedProduct->hasValidDiscount())
                                <div class="pd-reco-old">{{ $money($relatedProduct->original_price) }}</div>
                            @endif
                            <div class="pd-reco-meta">{{ $relatedProduct->customerAvailability() === 'in_stock' ? 'In stock' : 'Out of stock' }} · {{ $relatedProduct->working_days }} day(s)</div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= Tabs + details ================= --}}
        <div class="pd-detail-layout">
            <div class="pd-detail-main">
                <nav class="pd-tabs" aria-label="Product sections">
                    <a href="#attributes" class="is-active">Attributes</a>
                    @if($shopProduct->features && count($shopProduct->features) > 0)<a href="#features">Features</a>@endif
                    @if($shopProduct->description)<a href="#description">Description</a>@endif
                    <a href="#delivery">Delivery</a>
                    <a href="#reviews">Reviews ({{ $reviewSummary['count'] }})</a>
                </nav>

                <section id="attributes" class="pd-section">
                    <h2 class="pd-section-title">Key attributes</h2>
                    @if($allAttributes->isNotEmpty())
                        <div class="pd-attr-table">
                            @foreach($allAttributes as $attribute)
                                <div class="pd-attr-label">{{ $attribute['label'] }}</div>
                                <div class="pd-attr-value">{{ $attribute['value'] !== '' ? $attribute['value'] : '—' }}</div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No specifications listed for this product yet.</p>
                    @endif
                </section>

                @if($shopProduct->features && count($shopProduct->features) > 0)
                    <section id="features" class="pd-section">
                        <h2 class="pd-section-title">Features &amp; what's included</h2>
                        <ul class="pd-features">
                            @foreach($shopProduct->features as $feature)
                                <li><i class="bi bi-check-circle-fill"></i> {{ $feature }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section id="delivery" class="pd-section">
                    <h2 class="pd-section-title">Packaging and delivery</h2>
                    <div class="pd-attr-table">
                        <div class="pd-attr-label">Selling unit</div>
                        <div class="pd-attr-value">Single item</div>
                        <div class="pd-attr-label">Delivery time</div>
                        <div class="pd-attr-value">{{ $shopProduct->working_days }} working day(s)</div>
                    </div>

                    <details class="pd-details">
                        <summary>Lead time <i class="bi bi-chevron-down"></i></summary>
                        <p>
                            Orders are prepared after confirmation and delivered within about
                            <strong>{{ $shopProduct->working_days }} working day(s)</strong>. Larger quantities may take longer;
                            <a href="{{ $whatsappBase }}" target="_blank" rel="noopener">contact us</a> for bulk orders.
                        </p>
                    </details>
                </section>

                @if($shopProduct->description)
                    <section id="description" class="pd-section">
                        <h2 class="pd-section-title">Description</h2>
                        <div class="pd-description">{!! nl2br(e($shopProduct->description)) !!}</div>
                    </section>
                @endif

                @include('front.pages.partials.product-reviews')
            </div>
        </div>
    </div>
</main>

{{-- Image Modal for Gallery --}}
<div class="modal fade" id="imageModal" tabindex="-1" aria-label="Product image">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 bg-transparent">
            <button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0">
                <img id="modalImage" src="" alt="Full view" class="w-100 rounded-3 bg-white">
            </div>
        </div>
    </div>
</div>

<style>
    .pd-page { background: #f5f6f7; color: #222; font-family: 'DM Sans', sans-serif; padding: 1.25rem 0 3rem; }
    .pd-container { max-width: 1320px; }
    .pd-breadcrumb { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; font-size: .8rem; color: #767676; margin-bottom: 1rem; }
    .pd-breadcrumb a { color: #767676; text-decoration: none; }
    .pd-breadcrumb a:hover { color: #16803c; }
    .pd-breadcrumb i { font-size: .65rem; }

    /* ---- Top 3-column area ---- */
    .pd-top { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr) 320px; gap: 1.25rem; align-items: start; }
    .pd-gallery-col, .pd-info-col { background: #fff; border-radius: 12px; padding: 1rem; }
    .pd-info-col { padding: 1.25rem; }

    .pd-gallery { display: flex; gap: .75rem; }
    .pd-thumbs { display: flex; flex-direction: column; gap: .5rem; max-height: 480px; overflow-y: auto; flex: 0 0 64px; scrollbar-width: thin; }
    .pd-thumb { border: 2px solid transparent; border-radius: 8px; padding: 0; overflow: hidden; width: 60px; height: 60px; flex: 0 0 60px; background: #f3f3f3; }
    .pd-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .pd-thumb.is-active, .pd-thumb:hover { border-color: #16803c; }
    .pd-main-image { position: relative; flex: 1; aspect-ratio: 1 / 1; border-radius: 10px; overflow: hidden; background: #f3f3f3; }
    .pd-main-image img { width: 100%; height: 100%; object-fit: contain; cursor: zoom-in; }
    .pd-no-image { display: flex; height: 100%; align-items: center; justify-content: center; font-size: 4rem; color: #ccc; }
    .pd-arrow { position: absolute; top: 50%; transform: translateY(-50%); width: 36px; height: 36px; border-radius: 50%; border: 0; background: rgba(255,255,255,.92); box-shadow: 0 2px 8px rgba(0,0,0,.15); display: flex; align-items: center; justify-content: center; }
    .pd-arrow.is-prev { left: .6rem; } .pd-arrow.is-next { right: .6rem; }
    .pd-counter { position: absolute; bottom: .6rem; left: 50%; transform: translateX(-50%); background: rgba(0,0,0,.55); color: #fff; font-size: .75rem; padding: .15rem .6rem; border-radius: 99px; }
    .pd-zoom { position: absolute; top: .6rem; right: .6rem; width: 32px; height: 32px; border-radius: 50%; border: 0; background: rgba(255,255,255,.92); box-shadow: 0 2px 8px rgba(0,0,0,.12); }

    .pd-seller { display: grid; grid-template-columns: auto 1fr; gap: .25rem .75rem; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee; }
    .pd-seller-logo { width: 40px; height: 40px; border-radius: 8px; background: #16803c; color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; font-family: 'Space Grotesk', sans-serif; }
    .pd-seller-name { font-weight: 700; font-size: .92rem; }
    .pd-seller-meta { font-size: .78rem; color: #767676; }
    .pd-seller-badges { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .76rem; color: #555; margin-top: .4rem; }
    .pd-seller-badges i { color: #16803c; }

    .pd-title { font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.15rem, 2vw, 1.45rem); font-weight: 700; line-height: 1.3; margin: 0 0 .5rem; overflow-wrap: anywhere; }
    .pd-subline { display: flex; flex-wrap: wrap; gap: .3rem 1rem; font-size: .8rem; color: #767676; margin-bottom: 1rem; }

    .pd-price-box { background: #fafafa; border-radius: 10px; padding: .9rem 1rem; margin-bottom: 1.1rem; }
    .pd-price-tiers { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: .75rem; margin-bottom: .75rem; padding-bottom: .75rem; border-bottom: 1px dashed #e2e2e2; }
    .pd-tier-price { font-size: 1.15rem; font-weight: 700; color: #222; }
    .pd-tier-label { font-size: .75rem; color: #767676; }
    .pd-price-line { display: flex; flex-wrap: wrap; align-items: baseline; gap: .6rem; }
    .pd-price { font-size: 1.75rem; font-weight: 800; color: #16803c; line-height: 1.1; }
    .pd-old-price { color: #999; text-decoration: line-through; font-size: .95rem; }
    .pd-discount { background: #fdecec; color: #d0302f; font-weight: 700; font-size: .78rem; border-radius: 4px; padding: .1rem .4rem; }

    .pd-block { border: 0; padding: 0; margin: 0 0 1.1rem; }
    .pd-block-title { font-weight: 700; font-size: .95rem; margin-bottom: .6rem; float: none; width: 100%; }
    .pd-block-hint { font-weight: 400; color: #767676; font-size: .82rem; margin-left: .4rem; }
    .pd-options { display: flex; flex-wrap: wrap; gap: .5rem; }
    .pd-option { position: relative; display: flex; flex-direction: column; min-width: 92px; padding: .5rem .75rem; border: 1px solid #d9d9d9; border-radius: 8px; background: #fff; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
    .pd-option input { position: absolute; opacity: 0; pointer-events: none; }
    .pd-option:hover { border-color: #16803c; }
    .pd-option.is-selected { border-color: #16803c; box-shadow: 0 0 0 1px #16803c; background: #f3faf5; }
    .pd-option:focus-within { outline: 3px solid rgba(22,128,60,.25); outline-offset: 2px; }
    .pd-option-value { font-size: .86rem; font-weight: 600; overflow-wrap: anywhere; }
    .pd-option-price { font-size: .75rem; color: #16803c; font-weight: 700; }

    .pd-key-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .9rem 1rem; margin: 0; padding: .9rem 1rem; border: 1px solid #eee; border-radius: 10px; }
    .pd-key-grid dt { font-size: .74rem; color: #767676; font-weight: 400; }
    .pd-key-grid dd { font-size: .86rem; font-weight: 600; margin: 0; overflow-wrap: anywhere; }
    .pd-link { display: inline-block; margin-top: .5rem; font-size: .82rem; color: #16803c; text-decoration: none; font-weight: 600; }
    .pd-short-description { color: #555; font-size: .9rem; line-height: 1.6; margin: 0; }

    /* ---- Order card ---- */
    .pd-order-col { position: sticky; top: 1rem; }
    .pd-order-card { background: #fff; border-radius: 12px; padding: 1.1rem; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
    .pd-order-section { padding-bottom: .9rem; margin-bottom: .9rem; border-bottom: 1px solid #f0f0f0; }
    .pd-order-heading { font-weight: 700; font-size: .92rem; margin-bottom: .45rem; }
    .pd-order-text { font-size: .82rem; color: #555; margin: 0; }
    .pd-order-text i { color: #16803c; }
    .pd-order-total { font-size: .85rem; color: #555; margin-top: .6rem; }
    .purchase-quantity { display: inline-grid; grid-template-columns: 38px 56px 38px; height: 38px; border: 1px solid #d9d9d9; border-radius: 8px; overflow: hidden; background: #fff; }
    .purchase-quantity-button { border: 0; background: #fff; display: flex; align-items: center; justify-content: center; }
    .purchase-quantity-button:hover { background: #f5f5f5; }
    .purchase-quantity-value { width: 56px; border: 0; border-left: 1px solid #d9d9d9; border-right: 1px solid #d9d9d9; text-align: center; font-weight: 700; appearance: textfield; -moz-appearance: textfield; }
    .purchase-quantity-value::-webkit-inner-spin-button, .purchase-quantity-value::-webkit-outer-spin-button { appearance: none; margin: 0; }
    .pd-protection { list-style: none; padding: 0; margin: 0; display: grid; gap: .7rem; }
    .pd-protection li { display: flex; gap: .6rem; font-size: .8rem; }
    .pd-protection li > i { color: #16803c; font-size: 1.05rem; }
    .pd-protection strong { display: block; font-size: .83rem; }
    .pd-protection small { display: block; color: #767676; line-height: 1.4; }
    .pd-pay-logos { display: flex; gap: .4rem; margin: .25rem 0; }
    .pd-pay-logos img { height: 18px; width: auto; }
    .pd-order-actions { display: grid; gap: .6rem; }
    .pd-btn { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; min-height: 44px; border-radius: 999px; font-weight: 700; font-size: .92rem; text-decoration: none; border: 1px solid transparent; transition: background .15s, color .15s; }
    .pd-btn-primary { background: #16803c; border-color: #16803c; color: #fff; }
    .pd-btn-primary:hover:not(:disabled) { background: #116b32; color: #fff; }
    .pd-btn-primary:disabled { opacity: .55; cursor: not-allowed; }
    .pd-btn-outline { background: #fff; border-color: #222; color: #222; }
    .pd-btn-outline:hover { background: #222; color: #fff; }
    .pd-btn-whatsapp { background: #fff; border-color: #25d366; color: #128c4a; }
    .pd-btn-whatsapp:hover { background: #25d366; color: #fff; }

    /* ---- Sections below ---- */
    .pd-section { background: #fff; border-radius: 12px; padding: 1.25rem; margin-top: 1.25rem; scroll-margin-top: 4.5rem; }
    .pd-section-title { font-family: 'Space Grotesk', sans-serif; font-size: 1.1rem; font-weight: 700; margin: 0 0 1rem; }

    .pd-reco-head { display: flex; justify-content: space-between; align-items: center; }
    .pd-reco-head .pd-section-title { margin: 0; }
    .pd-reco-nav { display: flex; gap: .4rem; }
    .pd-reco-nav button { width: 32px; height: 32px; border-radius: 50%; border: 1px solid #ddd; background: #fff; }
    .pd-reco-track { display: grid; grid-auto-flow: column; grid-auto-columns: 180px; gap: 1rem; overflow-x: auto; scroll-snap-type: x mandatory; padding: 1rem 0 .25rem; scrollbar-width: none; }
    .pd-reco-track::-webkit-scrollbar { display: none; }
    .pd-reco-card { scroll-snap-align: start; color: inherit; text-decoration: none; display: flex; flex-direction: column; text-align: center; }
    .pd-reco-image { position: relative; aspect-ratio: 1 / 1; border-radius: 10px; overflow: hidden; background: #f3f3f3; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #ccc; }
    .pd-reco-image img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s ease; }
    .pd-reco-card:hover img { transform: scale(1.05); }
    .pd-reco-badge { position: absolute; top: .4rem; left: .4rem; background: #d0302f; color: #fff; font-size: .7rem; font-weight: 700; padding: .1rem .4rem; border-radius: 4px; }
    .pd-reco-name { font-size: .82rem; margin-top: .5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.4em; }
    .pd-reco-card:hover .pd-reco-name { text-decoration: underline; }
    .pd-reco-rating { font-size: .72rem; color: #767676; margin-top: .15rem; }
    .pd-rating-link { display: inline-flex; align-items: center; gap: .35rem; color: #555; text-decoration: none; }
    .pd-rating-link:hover span { text-decoration: underline; }
    .pd-reco-price { font-weight: 700; font-size: .92rem; margin-top: .2rem; }
    .pd-reco-old { font-size: .75rem; color: #999; text-decoration: line-through; }
    .pd-reco-meta { font-size: .72rem; color: #767676; margin-top: .15rem; }

    .pd-detail-layout { max-width: 980px; }
    .pd-tabs { position: sticky; top: 0; z-index: 5; display: flex; gap: 1.5rem; background: #fff; border-radius: 12px; padding: 0 1.25rem; margin-top: 1.25rem; border-bottom: 1px solid #eee; overflow-x: auto; }
    .pd-tabs a { padding: .9rem 0; color: #555; text-decoration: none; font-weight: 600; font-size: .9rem; border-bottom: 2px solid transparent; white-space: nowrap; }
    .pd-tabs a.is-active, .pd-tabs a:hover { color: #222; border-bottom-color: #222; }

    .pd-attr-table { display: grid; grid-template-columns: minmax(110px, 1fr) minmax(0, 1.4fr) minmax(110px, 1fr) minmax(0, 1.4fr); border: 1px solid #e8e8e8; border-radius: 8px; overflow: hidden; font-size: .84rem; }
    .pd-attr-label, .pd-attr-value { padding: .7rem .9rem; border-bottom: 1px solid #e8e8e8; overflow-wrap: anywhere; }
    .pd-attr-label { background: #f7f7f7; color: #666; }
    .pd-attr-value { color: #222; font-weight: 500; }
    .pd-features { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .6rem; }
    .pd-features li { display: flex; gap: .55rem; font-size: .9rem; background: #f8faf8; border-radius: 8px; padding: .65rem .8rem; }
    .pd-features i { color: #16803c; }
    .pd-details { margin-top: 1rem; border-top: 1px solid #eee; }
    .pd-details summary { display: flex; justify-content: space-between; align-items: center; padding: .9rem 0; font-weight: 700; cursor: pointer; list-style: none; }
    .pd-details summary::-webkit-details-marker { display: none; }
    .pd-details[open] summary i { transform: rotate(180deg); }
    .pd-details p { font-size: .88rem; color: #555; margin: 0; }
    .pd-details a { color: #16803c; }
    .pd-description { font-size: .92rem; line-height: 1.7; color: #444; overflow-wrap: anywhere; }

    @media (max-width: 1199.98px) {
        .pd-top { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
        .pd-order-col { grid-column: 1 / -1; position: static; }
    }
    @media (max-width: 767.98px) {
        .pd-page { padding-top: .75rem; }
        .pd-top { grid-template-columns: 1fr; gap: .9rem; }
        .pd-gallery { flex-direction: column-reverse; }
        .pd-thumbs { flex-direction: row; max-height: none; overflow-x: auto; flex: none; }
        .pd-key-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pd-attr-table { grid-template-columns: minmax(100px, .8fr) minmax(0, 1.2fr); }
        .pd-reco-track { grid-auto-columns: 150px; }
    }
</style>

<script>
    (() => {
        // ---- Gallery ----
        const mainImage = document.getElementById('productMainImage');
        const modalImage = document.getElementById('modalImage');
        const thumbnails = Array.from(document.querySelectorAll('.gallery-thumbnail'));
        const counter = document.getElementById('galleryCounter');
        let activeIndex = 0;

        const selectImage = (index) => {
            if (!mainImage || thumbnails.length === 0) return;
            activeIndex = (index + thumbnails.length) % thumbnails.length;
            mainImage.src = thumbnails[activeIndex].dataset.gallerySrc;
            thumbnails.forEach((thumbnail, i) => {
                thumbnail.classList.toggle('is-active', i === activeIndex);
                thumbnail.setAttribute('aria-pressed', i === activeIndex ? 'true' : 'false');
            });
            thumbnails[activeIndex].scrollIntoView({ block: 'nearest', inline: 'nearest' });
            if (counter) counter.textContent = String(activeIndex + 1);
        };

        thumbnails.forEach((thumbnail, index) => {
            thumbnail.addEventListener('click', () => selectImage(index));
            thumbnail.addEventListener('mouseenter', () => selectImage(index));
        });
        document.querySelectorAll('[data-gallery-step]').forEach((button) => {
            button.addEventListener('click', () => selectImage(activeIndex + Number(button.dataset.galleryStep)));
        });
        document.getElementById('imageModal')?.addEventListener('show.bs.modal', () => {
            if (mainImage && modalImage) modalImage.src = mainImage.src;
        });
        if (mainImage) {
            let touchStartX = null;
            mainImage.addEventListener('touchstart', (event) => { touchStartX = event.changedTouches[0].screenX; }, { passive: true });
            mainImage.addEventListener('touchend', (event) => {
                if (touchStartX === null) return;
                const distance = event.changedTouches[0].screenX - touchStartX;
                if (Math.abs(distance) > 40) selectImage(activeIndex + (distance < 0 ? 1 : -1));
                touchStartX = null;
            }, { passive: true });
        }

        // ---- Recommendations carousel ----
        const recoTrack = document.getElementById('recoTrack');
        document.querySelectorAll('[data-reco-scroll]').forEach((button) => {
            button.addEventListener('click', () => {
                recoTrack?.scrollBy({ left: Number(button.dataset.recoScroll) * recoTrack.clientWidth * 0.8, behavior: 'smooth' });
            });
        });

        // ---- Tabs: highlight the section in view ----
        const tabLinks = Array.from(document.querySelectorAll('.pd-tabs a'));
        if ('IntersectionObserver' in window && tabLinks.length) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting) return;
                    tabLinks.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id));
                });
            }, { rootMargin: '-30% 0px -60% 0px' });
            tabLinks.forEach((link) => {
                const section = document.querySelector(link.getAttribute('href'));
                if (section) observer.observe(section);
            });
        }

        // ---- Purchase: quantity, options, links ----
        const quantity = document.getElementById('purchaseQuantity');
        const minus = document.getElementById('purchaseQuantityMinus');
        const plus = document.getElementById('purchaseQuantityPlus');
        const choices = document.querySelectorAll('[data-product-variation-choice]');
        const price = document.getElementById('selectedProductPrice');
        const total = document.getElementById('purchaseTotal');
        const variationName = document.getElementById('selectedVariationName');
        const cartVariation = document.getElementById('selectedVariationId');
        const requestButton = document.getElementById('requestProductButton');
        const whatsappButton = document.getElementById('purchaseWhatsappButton');
        const money = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });
        const basePrice = Number({{ json_encode((float) $initialPurchasePrice) }});

        const clampQuantity = () => {
            const max = Math.max(1, Number(quantity.max) || 100);
            const value = Math.max(1, Math.min(max, Number.parseInt(quantity.value, 10) || 1));
            quantity.value = String(value);
            return value;
        };
        const selectedChoice = () => document.querySelector('[data-product-variation-choice]:checked');
        const currentPrice = () => selectedChoice() ? Number(selectedChoice().dataset.variationPrice) : basePrice;

        const updatePurchase = () => {
            const value = clampQuantity();
            const choice = selectedChoice();
            if (total) total.textContent = `RWF ${money.format(currentPrice() * value)}`;

            if (requestButton) {
                const requestUrl = new URL(requestButton.href);
                requestUrl.searchParams.set('quantity', value);
                if (choice) requestUrl.searchParams.set('variation_id', choice.value);
                requestButton.href = requestUrl.toString();
            }
            if (whatsappButton) {
                const choiceName = choice ? ` — ${choice.dataset.variationName}` : '';
                const message = `Hi TRESCA Hub! I'm interested in: *${whatsappButton.dataset.productName}${choiceName}*\nQuantity: ${value}\nPrice: RWF ${money.format(currentPrice())}\nPlease share more details.\n\nProduct Link: ${whatsappButton.dataset.productUrl}`;
                whatsappButton.href = `${whatsappButton.dataset.whatsappBase}?text=${encodeURIComponent(message)}`;
            }
        };

        minus?.addEventListener('click', () => { quantity.value = String(Math.max(1, clampQuantity() - 1)); updatePurchase(); });
        plus?.addEventListener('click', () => {
            const max = Math.max(1, Number(quantity.max) || 100);
            quantity.value = String(Math.min(max, clampQuantity() + 1));
            updatePurchase();
        });
        quantity?.addEventListener('input', updatePurchase);
        quantity?.addEventListener('change', updatePurchase);

        choices.forEach((choice) => {
            choice.addEventListener('change', () => {
                if (!choice.checked) return;
                if (price) price.textContent = `RWF ${money.format(Number(choice.dataset.variationPrice))}`;
                if (variationName) variationName.textContent = choice.dataset.variationName;
                if (cartVariation) cartVariation.value = choice.value;
                choices.forEach((option) => option.closest('.product-variation-option')?.classList.toggle('is-selected', option === choice));
                updatePurchase();
            });
        });

        if (quantity) updatePurchase();
    })();
</script>

@endsection
