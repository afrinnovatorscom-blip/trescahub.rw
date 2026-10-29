@extends('front.layouts.guest')
@section('content')
@php
    $productGallery = array_values(array_unique(array_filter(array_merge(
        [$shopProduct->thumbnail],
        $shopProduct->images ?? []
    ))));
    $productGalleryUrl = fn ($path) => asset('assets/' . ltrim(str_replace('public/assets/', '', $path), '/'));
    $productVariations = $shopProduct->variations->filter(fn ($variation) =>
        filled($variation->feature_label)
        && filled($variation->variation_value)
        && is_numeric($variation->price)
        && (float) $variation->price >= 0
    )->values();
    $selectedProductVariation = $productVariations->firstWhere('is_default', true) ?? $productVariations->first();
@endphp

<main class="py-5 py-md-20">
    <div class="container">

        {{-- Back Button --}}
        <a href="{{ route('shop') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-muted mb-4" style="font-size: 0.95rem;">
            <i class="bi bi-arrow-left"></i>
            Back to Shop
        </a>

        {{-- Product Detail --}}
        <div class="row gx-4 gy-5 g-lg-5 mt-2">
            {{-- Left: Image --}}
            <div class="col-12 col-lg-5">
                <div class="rounded-3 overflow-hidden bg-light shadow-sm">
                    @if(count($productGallery) > 0)
                        <img id="productMainImage" src="{{ $productGalleryUrl($productGallery[0]) }}"
                             alt="{{ $shopProduct->product_name }}"
                             class="w-100 img-fluid"
                             style="aspect-ratio: 1 / 1; object-fit: cover; cursor: zoom-in;"
                             data-bs-toggle="modal" data-bs-target="#imageModal">
                    @else
                        <div class="d-flex align-items-center justify-content-center"
                             style="aspect-ratio: 1 / 1; font-size: 4rem; color: #ccc;">
                            <i class="bi bi-box-seam"></i>
                        </div>
                    @endif
                </div>

                {{-- Badges --}}
                <div class="d-flex gap-2 mt-4 flex-wrap">
                    @if($shopProduct->customerAvailability() === 'in_stock')
                        <span class="badge bg-success" style="font-size: 0.8rem;">In Stock</span>
                    @else
                        <span class="badge bg-danger" style="font-size: 0.8rem;">Out of Stock</span>
                    @endif
                    @if($shopProduct->category)
                        <span class="badge bg-light text-dark" style="font-size: 0.8rem;">
                            {{ $shopProduct->category->name }}
                        </span>
                    @endif
                </div>

                {{-- Additional Images Gallery (if exists) --}}
                @if(count($productGallery) > 0)
                    <div class="mt-4">
                        <h6 class="text-muted mb-3" style="font-size: 0.9rem;">Gallery</h6>
                        <div class="d-flex flex-wrap gap-2" id="productGallery">
                            @foreach($productGallery as $index => $image)
                                <button type="button" class="btn p-0 border rounded-2 overflow-hidden gallery-thumbnail {{ $index === 0 ? 'border-primary' : '' }}"
                                        data-gallery-index="{{ $index }}"
                                        data-gallery-src="{{ $productGalleryUrl($image) }}"
                                        aria-label="View product image {{ $index + 1 }}"
                                        aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                    <img src="{{ $productGalleryUrl($image) }}"
                                         alt="Product image {{ $index + 1 }}"
                                         style="width:76px;height:76px;object-fit:cover;">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Details --}}
            <div class="col-12 col-lg-7">
                {{-- Category Badge --}}
                @if($shopProduct->category)
                    <span class="badge bg-secondary-subtle text-dark rounded-pill px-3 py-2" style="font-size: 0.85rem;">
                        {{ $shopProduct->category->name }}
                    </span>
                @endif

                {{-- Title --}}
                <h1 class="mt-3 fw-bold" style="font-size: 2.5rem;">
                    {{ $shopProduct->product_name }}
                </h1>

                {{-- Description --}}
                @if($shopProduct->description)
                    <p class="mt-4 text-muted lead">
                        {{ $shopProduct->description }}
                    </p>
                @endif

                {{-- Price & Order Section --}}
                <div class="mt-5 rounded-3 p-4 shadow-sm" style="background-color: #f8f9fa;">
                    @if($productVariations->isNotEmpty())
                        <fieldset id="productVariationSelector" class="mb-3 border-0 p-0">
                            <legend class="h6 fw-semibold mb-2">Choose your preferred option</legend>
                            <div class="product-variation-options">
                                @foreach($productVariations as $variation)
                                    <label class="product-variation-option {{ $variation->id === $selectedProductVariation->id ? 'is-selected' : '' }}">
                                        <input
                                            type="radio"
                                            name="product_variation_choice"
                                            value="{{ $variation->id }}"
                                            data-product-variation-choice
                                            data-variation-price="{{ $variation->price }}"
                                            @checked($variation->id === $selectedProductVariation->id)
                                            aria-label="{{ $variation->feature_label }}: {{ $variation->variation_value }}, RWF {{ number_format((float) $variation->price, 0, '.', ',') }}"
                                        >
                                        <span class="product-variation-description">
                                            <span class="product-variation-label">{{ $variation->feature_label }}</span>
                                            <span class="product-variation-value">{{ $variation->variation_value }}</span>
                                        </span>
                                        <span class="product-variation-price">RWF {{ number_format((float) $variation->price, 0, '.', ',') }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif
                    <div class="d-flex align-items-end gap-2">
                        <span @if($productVariations->isNotEmpty()) id="selectedProductPrice" @endif class="fw-bold" style="font-size: 2.5rem; color: #16a34a;">
                            RWF {{ number_format($selectedProductVariation ? (float) $selectedProductVariation->price : $shopProduct->price, 0, '.', ',') }}
                        </span>
                        @if($shopProduct->hasValidDiscount())
                            <span class="badge bg-danger mb-2">{{ $shopProduct->discount_percentage }}% off</span>
                        @endif
                    </div>
                    @if($shopProduct->hasValidDiscount())
                        <div class="text-muted text-decoration-line-through">RWF {{ number_format($shopProduct->original_price, 0, '.', ',') }}</div>
                    @endif

                    {{-- Working Days --}}
                    <div class="d-flex align-items-center gap-2 mt-3 text-muted" style="font-size: 0.95rem;">
                        <i class="bi bi-clock"></i>
                        <span>Delivery: {{ $shopProduct->working_days }} working day(s)</span>
                    </div>

                    {{-- WhatsApp Order Button --}}
                    @php
                        $whatsappMessage = urlencode(
                            "Hi TRESCA Hub! I'm interested in: *" . $shopProduct->product_name . "*\n" .
                            "Price: RWF " . number_format($shopProduct->price, 0, '.', ',') . "\n" .
                            "Please share more details.\n\n" .
                            "Product Link: " . route('shop-products.show', $shopProduct)
                        );
                        $whatsappUrl = "https://wa.me/250780290040?text=" . $whatsappMessage;
                    @endphp

                    <div class="mt-4 d-grid gap-2">
                        <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-success btn-lg w-100 gap-2" style="background-color: #25d366; border-color: #25d366;">
                            <i class="bi bi-whatsapp"></i>
                            Order via WhatsApp
                        </a>
                        <form method="POST" action="{{ route('cart.add') }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="product" value="{{ $shopProduct->slug }}">
                            @if($productVariations->isNotEmpty())
                                <input id="selectedVariationId" type="hidden" name="variation_id" value="{{ $selectedProductVariation->id }}">
                            @endif
                            <input type="number" name="quantity" value="1" min="1" max="100" class="form-control" style="max-width: 110px;" aria-label="Quantity">
                            <button class="btn btn-primary btn-lg flex-grow-1" type="submit" @if($shopProduct->customerAvailability() !== 'in_stock') disabled @endif>Add to cart</button>
                        </form>
                        <a
                            id="requestProductButton"
                            href="{{ route('product-requests.create', ['product' => $shopProduct->slug] + ($selectedProductVariation ? ['variation_id' => $selectedProductVariation->id] : [])) }}"
                            class="btn btn-outline-dark btn-lg"
                        >Request this product</a>
                    </div>

                    {{-- Stock Status --}}
                    @if($shopProduct->customerAvailability() === 'out_of_stock')
                        <div class="alert alert-warning mt-3 mb-0" role="alert">
                            <i class="bi bi-exclamation-triangle"></i>
                            This product is currently out of stock. Contact us for availability.
                        </div>
                    @endif
                </div>

                {{-- Features --}}
                @if($shopProduct->features && count($shopProduct->features) > 0)
                    <div class="mt-6">
                        <h5 class="fw-bold">Features & What's Included</h5>
                        <div class="row g-3 mt-2">
                            @foreach($shopProduct->features as $feature)
                                <div class="col-12 col-sm-6">
                                    <div class="d-flex gap-3 p-3 bg-light rounded-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-success text-white" style="width: 24px; height: 24px; flex-shrink: 0; font-size: 0.75rem;">
                                            ✓
                                        </div>
                                        <span class="text-sm" style="font-size: 0.95rem;">{{ $feature }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Specifications --}}
                @if($shopProduct->specifications && count($shopProduct->specifications) > 0)
                    <div class="mt-6">
                        <h5 class="fw-bold">Specifications</h5>
                        <div class="mt-3 rounded-3 overflow-hidden shadow-sm">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    @foreach($shopProduct->specifications as $spec)
                                        <tr>
                                            <td class="text-muted fw-semibold" style="padding: 0.75rem 1rem;">
                                                {{ $spec['label'] ?? $spec }}
                                            </td>
                                            <td class="fw-semibold text-end" style="padding: 0.75rem 1rem;">
                                                {{ $spec['value'] ?? '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Additional WhatsApp CTA --}}
                <div class="mt-4">
                    <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-outline-secondary gap-2">
                        <i class="bi bi-chat-dots"></i>
                        Ask Questions via WhatsApp
                    </a>
                </div>
            </div>
        </div>

        {{-- Related Products --}}
        @if(isset($related) && $related->count() > 0)
            <section class="mt-12 pt-5 border-top">
                <h3 class="fw-bold mb-4">Related Products</h3>
                <div class="row g-4">
                    @foreach($related as $relatedProduct)
                        <div class="col-12 col-sm-6 col-lg-3">
                            <a href="{{ route('shop-products.show', $relatedProduct) }}" class="text-decoration-none">
                                <div class="card h-100 shadow-sm product-card">

                                    {{-- Thumbnail --}}
                                    <div class="overflow-hidden bg-light" style="height: 180px;">
                                        @if($relatedProduct->thumbnail)
                                            <img src="{{ asset('assets/' . ltrim(str_replace('public/assets/', '', $relatedProduct->thumbnail), '/')) }}"
                                                 alt="{{ $relatedProduct->product_name }}"
                                                 class="w-100 h-100"
                                                 style="object-fit: cover; transition: transform 0.3s ease;">
                                        @else
                                            <div class="d-flex align-items-center justify-content-center h-100" style="font-size: 2rem; color: #ccc;">
                                                <i class="bi bi-box-seam"></i>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="card-body d-flex flex-column">

                                        {{-- Badges --}}
                                        <div class="d-flex gap-2 mb-2 flex-wrap">
                                            @if($relatedProduct->category)
                                                <span class="badge bg-light text-dark" style="font-size: 0.7rem;">
                                                    {{ $relatedProduct->category->name }}
                                                </span>
                                            @endif
                                            @if($relatedProduct->hasValidDiscount())
                                                <span class="badge bg-danger" style="font-size: 0.7rem;">{{ $relatedProduct->discount_percentage }}% off</span>
                                            @endif
                                            <span class="badge {{ $relatedProduct->customerAvailability() === 'in_stock' ? 'bg-success' : 'bg-danger' }}"
                                                  style="font-size: 0.7rem;">
                                                {{ $relatedProduct->customerAvailability() === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                                            </span>
                                        </div>

                                        {{-- Title --}}
                                        <h6 class="card-title fw-bold mb-2" style="font-size: 0.95rem;">
                                            {{ $relatedProduct->product_name }}
                                        </h6>

                                        {{-- Description --}}
                                        @if($relatedProduct->description)
                                            <p class="card-text text-muted small flex-grow-1 mb-3"
                                               style="font-size: 0.85rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                                {{ $relatedProduct->description }}
                                            </p>
                                        @endif

                                        {{-- Working Days --}}
                                        <div class="d-flex align-items-center gap-1 mb-3 text-muted" style="font-size: 0.8rem;">
                                            <i class="bi bi-clock"></i>
                                            <span>{{ $relatedProduct->working_days }} day(s)</span>
                                        </div>

                                        {{-- Price & Button --}}
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <span class="fw-bold text-success" style="font-size: 1.25rem;">
                                                RWF {{ number_format($relatedProduct->price, 0, '.', ',') }}
                                                @if($relatedProduct->hasValidDiscount())
                                                    <span class="d-block small text-muted text-decoration-line-through">RWF {{ number_format($relatedProduct->original_price, 0, '.', ',') }}</span>
                                                @endif
                                            </span>
                                            <span class="btn btn-sm btn-success">
                                                <i class="bi bi-eye me-1"></i> View
                                            </span>
                                        </div>

                                        <script>
                                        document.addEventListener('DOMContentLoaded', function () {
                                            const mainImage = document.getElementById('productMainImage');
                                            const thumbnails = Array.from(document.querySelectorAll('.gallery-thumbnail'));
                                            const modalImage = document.getElementById('modalImage');
                                            if (!mainImage || thumbnails.length === 0) return;

                                            let activeIndex = 0;
                                            const selectImage = function (index) {
                                                activeIndex = (index + thumbnails.length) % thumbnails.length;
                                                mainImage.src = thumbnails[activeIndex].dataset.gallerySrc;
                                                thumbnails.forEach((thumbnail, thumbnailIndex) => {
                                                    thumbnail.classList.toggle('border-primary', thumbnailIndex === activeIndex);
                                                    thumbnail.setAttribute('aria-pressed', thumbnailIndex === activeIndex ? 'true' : 'false');
                                                });
                                                modalImage.src = mainImage.src;
                                            };

                                            thumbnails.forEach((thumbnail, index) => thumbnail.addEventListener('click', () => selectImage(index)));
                                            mainImage.addEventListener('click', () => { modalImage.src = mainImage.src; });

                                            let touchStartX = null;
                                            mainImage.addEventListener('touchstart', event => { touchStartX = event.changedTouches[0].screenX; }, { passive: true });
                                            mainImage.addEventListener('touchend', event => {
                                                if (touchStartX === null) return;
                                                const distance = event.changedTouches[0].screenX - touchStartX;
                                                if (Math.abs(distance) > 40) selectImage(activeIndex + (distance < 0 ? 1 : -1));
                                                touchStartX = null;
                                            }, { passive: true });
                                        });
                                        </script>

                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
</main>

{{-- Image Modal for Gallery --}}
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            <div class="modal-body p-0">
                <img id="modalImage" src="" alt="Full view" class="w-100 rounded-3">
            </div>
        </div>
    </div>
</div>

<style>
    #productVariationSelector .product-variation-options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    #productVariationSelector .product-variation-option {
        display: flex;
        flex: 0 1 180px;
        min-width: 140px;
        align-items: center;
        gap: 0.55rem;
        padding: 0.55rem 0.7rem;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        background: #fff;
        cursor: pointer;
    }

    #productVariationSelector .product-variation-option.is-selected {
        border-color: #198754;
        background: #f0fdf4;
        box-shadow: 0 0 0 1px #198754;
    }

    #productVariationSelector .product-variation-option input {
        flex: 0 0 auto;
        margin: 0;
        accent-color: #198754;
    }

    #productVariationSelector .product-variation-description {
        display: flex;
        min-width: 0;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 0.1rem;
        line-height: 1.25;
    }

    #productVariationSelector .product-variation-label {
        color: #6c757d;
        font-size: 0.75rem;
    }

    #productVariationSelector .product-variation-value {
        color: #212529;
        font-size: 0.9rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }

    #productVariationSelector .product-variation-price {
        color: #198754;
        font-size: 0.85rem;
        font-weight: 700;
        white-space: nowrap;
    }

    #productVariationSelector .product-variation-option:focus-within {
        outline: 3px solid rgba(25, 135, 84, 0.25);
        outline-offset: 2px;
    }

    .product-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        overflow: hidden;
        border: none;
    }

    .product-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04) !important;
        transform: translateY(-4px);
    }

    .product-card:hover img {
        transform: scale(1.05);
    }

    .py-md-20 { padding-top: 5rem; padding-bottom: 5rem; }

    @media (max-width: 768px) {
        .py-md-20 { padding-top: 3rem; padding-bottom: 3rem; }
    }
</style>

@if($productVariations->isNotEmpty())
    <script>
        (() => {
            const choices = document.querySelectorAll('[data-product-variation-choice]');
            const price = document.getElementById('selectedProductPrice');
            const cartVariation = document.getElementById('selectedVariationId');
            const requestButton = document.getElementById('requestProductButton');
            const money = new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 });

            choices.forEach((choice) => {
                choice.addEventListener('change', () => {
                    if (!choice.checked) return;

                    price.textContent = `RWF ${money.format(Number(choice.dataset.variationPrice))}`;
                    if (cartVariation) cartVariation.value = choice.value;

                    choices.forEach((option) => {
                        option.closest('.product-variation-option')?.classList.toggle('is-selected', option === choice);
                    });

                    if (requestButton) {
                        const requestUrl = new URL(requestButton.href);
                        requestUrl.searchParams.set('variation_id', choice.value);
                        requestButton.href = requestUrl.toString();
                    }
                });
            });
        })();
    </script>
@endif

@endsection