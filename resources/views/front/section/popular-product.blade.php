@if($popularProducts->count() > 0)
<section class="py-5" style="background-color: #ffffff;">
    <div class="container-xl px-4 px-lg-5">

        {{-- Section Header --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1" style="font-size: 1.6rem; color: #111;">
                    🛒 Popular Products
                </h2>
                <p class="mb-0" style="color: #888; font-size: 0.92rem;">
                    Top-selling items from our shop
                </p>
            </div>
            <a href="{{ route('shop') }}"
               class="btn btn-outline-secondary btn-sm px-4"
               style="border-radius: 8px; font-size: 0.85rem;">
                Visit Shop
            </a>
        </div>

        {{-- Products Flex Grid --}}
        <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">

            @foreach($popularProducts as $product)
            @php
                $whatsappMessage = urlencode(
                    "Hi TRESCA Hub! I'm interested in: *" . $product->product_name . "*\n" .
                    "Price: RWF " . number_format($product->price, 0, '.', ',') . "\n" .
                    "Please share more details.\n\n" .
                    "Product Link: " . route('shop-products.show', $product->slug)
                );
                $whatsappUrl = "https://wa.me/250780290040?text=" . $whatsappMessage;
            @endphp

            <div class="product-card"
                 style="flex: 0 0 calc(25% - 15px); width: calc(25% - 15px);
                        background: #fff; border-radius: 16px;
                        border: 1px solid #e5e7eb;
                        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                        overflow: hidden; display: flex; flex-direction: column;">

                {{-- Thumbnail --}}
                <a href="{{ route('shop-products.show', $product->slug) }}"
                   class="text-decoration-none">
                    <div style="height: 200px; overflow: hidden; background: #f5f5f5; position: relative;">
                        @if($product->thumbnail)
                            <img src="{{ asset('assets/' . $product->thumbnail) }}"
                                 alt="{{ $product->product_name }}"
                                 class="product-img"
                                 style="width:100%; height:100%; object-fit:cover; transition: transform 0.5s ease;">
                        @else
                            <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center;">
                                <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                            </div>
                        @endif
                    </div>
                </a>

                {{-- Card Body --}}
                <div style="padding: 14px 16px; display: flex; flex-direction: column; flex-grow: 1;">

                    {{-- Badges --}}
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px;">
                        @if($product->hasValidDiscount())
                            <span class="badge bg-danger" style="font-size: 0.68rem;">{{ $product->discount_percentage }}% off</span>
                        @endif
                        @if($product->category)
                            <span class="badge bg-light text-dark border"
                                  style="font-size: 0.68rem;">
                                {{ $product->category->name }}
                            </span>
                        @endif
                        <span class="badge {{ $product->status === 'in_stock' ? 'bg-success' : 'bg-danger' }}"
                              style="font-size: 0.68rem;">
                            {{ $product->status === 'in_stock' ? 'In Stock' : 'Out of Stock' }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <a href="{{ route('shop-products.show', $product->slug) }}"
                       class="text-decoration-none">
                        <h6 class="fw-bold mb-1"
                            style="color: #1a2035; font-size: 0.9rem; line-height: 1.4;">
                            {{ $product->product_name }}
                        </h6>
                    </a>

                    {{-- Description --}}
                    @if($product->description)
                        <p class="text-muted mb-2"
                           style="font-size: 0.8rem;
                                  display: -webkit-box;
                                  -webkit-line-clamp: 2;
                                  -webkit-box-orient: vertical;
                                  overflow: hidden;">
                            {{ $product->description }}
                        </p>
                    @endif

                    {{-- Price + Order --}}
                    <div style="display: flex; justify-content: space-between;
                                align-items: center; margin-top: auto; padding-top: 10px;">

                        <div>
                            <div class="fw-bold" style="color: #16a34a; font-size: 1rem;">
                                RWF {{ number_format($product->price, 0, '.', ',') }}
                            </div>
                            @if($product->hasValidDiscount())
                                <div class="small text-muted text-decoration-line-through">RWF {{ number_format($product->original_price, 0, '.', ',') }}</div>
                            @endif
                            <div style="font-size: 0.72rem; color: #aaa;">
                                {{ $product->working_days }}-{{ $product->working_days + 1 }} working days
                            </div>
                        </div>

                        <a href="{{ $whatsappUrl }}"
                           target="_blank"
                           style="background-color: #16a34a; color: white;
                                  border-radius: 8px; padding: 6px 12px;
                                  font-size: 0.82rem; font-weight: 600;
                                  text-decoration: none; white-space: nowrap;
                                  display: inline-flex; align-items: center; gap: 5px;">
                            <i class="bi bi-whatsapp"></i> Order
                        </a>

                    </div>
                </div>
            </div>
            @endforeach

        </div>

    </div>
</section>

<style>
    .product-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.12) !important;
    }
    .product-card:hover .product-img {
        transform: scale(1.06);
    }

    @media (max-width: 992px) {
        .product-card {
            flex: 0 0 calc(50% - 10px) !important;
            width: calc(50% - 10px) !important;
        }
    }
    @media (max-width: 576px) {
        .product-card {
            flex: 0 0 100% !important;
            width: 100% !important;
        }
    }
</style>
@endif