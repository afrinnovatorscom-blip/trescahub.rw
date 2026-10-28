@php
    $popularServices = \App\Models\Service::with('category')
        ->where('is_popular', true)
        ->latest()
        ->get();
@endphp

@if($popularServices->count() > 0)
    <section class="popular-services py-5">
        <div class="container">

            {{-- Section Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="popular-title fw-bold mb-1 d-flex align-items-center gap-2">
                        <span>🔥</span>
                        Popular Services
                    </h2>
                    <p class="text-muted mb-0">Most requested service packages</p>
                </div>
                <a href="{{ route('services') }}" class="btn btn-outline-secondary btn-sm px-4">
                    View All
                </a>
            </div>

            {{-- Scrollable Services Carousel --}}
            <div class="carousel-wrapper position-relative">

                {{-- Previous Button --}}
                <button 
                    class="carousel-btn carousel-btn-prev d-none d-md-flex" 
                    onclick="scrollServices(-320)"
                    aria-label="Previous services">
                    <i class="bi bi-chevron-left"></i>
                </button>

                {{-- Services Container --}}
                <div id="servicesScrollContainer" class="services-scroll d-flex gap-4 overflow-x-auto pb-3">

                    @foreach($popularServices as $service)
                        <div class="service-card flex-shrink-0">

                            {{-- Service Image --}}
                            <a href="{{ route('service.show', $service->slug) }}" class="service-image-link">
                                <div class="service-image-container">
                                    @if($service->thumbnail)
                                        <img 
                                            src="{{ asset('' . $service->thumbnail) }}"
                                            alt="{{ $service->title }}"
                                            class="service-image w-100 h-100"
                                            loading="lazy">
                                    @else
                                        <div class="image-placeholder w-100 h-100 d-flex align-items-center justify-content-center">
                                            <span class="placeholder-icon">📦</span>
                                        </div>
                                    @endif
                                </div>
                            </a>

                            {{-- Service Body --}}
                            <div class="service-body">

                                {{-- Service Title --}}
                                <a href="{{ route('service.show', $service->slug) }}" class="service-title-link">
                                    <h6 class="service-title fw-bold mb-2">
                                        {{ $service->title }}
                                    </h6>
                                </a>

                                {{-- What's Included --}}
                                <div class="included-section mb-3">
                                    <p class="included-label text-muted small mb-2">
                                        WHAT'S INCLUDED
                                    </p>
                                    <ul class="included-list list-unstyled">
                                        @php
                                            $includedItems = [];
                                            
                                            if (!empty($service->included_items)) {
                                                $includedItems = is_array($service->included_items) 
                                                    ? $service->included_items 
                                                    : json_decode($service->included_items, true) ?? [];
                                            } elseif (!empty($service->what_included)) {
                                                $includedItems = is_array($service->what_included) 
                                                    ? $service->what_included 
                                                    : json_decode($service->what_included, true) ?? [];
                                            } elseif (!empty($service->features)) {
                                                $includedItems = is_array($service->features) 
                                                    ? $service->features 
                                                    : json_decode($service->features, true) ?? [];
                                            }
                                        @endphp
                                        
                                        @forelse($includedItems as $item)
                                            <li class="included-item text-muted mb-1">
                                                <span class="checkmark">✓</span>
                                                <span>{{ is_array($item) ? $item['name'] ?? $item : $item }}</span>
                                            </li>
                                        @empty
                                            @if($service->description)
                                                <li class="included-item text-muted mb-1">
                                                    <span class="checkmark">✓</span>
                                                    <span>{{ Str::limit($service->description, 60) }}</span>
                                                </li>
                                            @endif
                                        @endforelse
                                    </ul>
                                </div>

                                {{-- Price and Order Button --}}
                                <div class="service-footer d-flex justify-content-between align-items-center mt-auto">
                                    <div class="service-price fw-bold">
                                        From RWF {{ number_format($service->price, 0, '.', ',') }}
                                    </div>
                                    <a href="{{ route('service.show', $service->slug) }}"
                                       class="btn-order d-flex align-items-center gap-1">
                                        <i class="bi bi-whatsapp"></i>
                                        <span>Order</span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    @endforeach

                </div>

                {{-- Next Button --}}
                <button 
                    class="carousel-btn carousel-btn-next d-none d-md-flex" 
                    onclick="scrollServices(320)"
                    aria-label="Next services">
                    <i class="bi bi-chevron-right"></i>
                </button>

            </div>
        </div>
    </section>

    <style>
        /* ========================
           Popular Services Section
           ======================== */
        .popular-services {
            background-color: #fff;
        }

        .popular-title {
            font-size: 1.75rem;
            color: #1a2035;
        }

        .popular-title span {
            font-size: 1.5rem;
            display: inline-block;
        }

        /* ========================
           Carousel Wrapper
           ======================== */
        .carousel-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .services-scroll {
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 0.75rem;
        }

        /* Hide scrollbar */
        .services-scroll::-webkit-scrollbar {
            display: none;
        }

        /* ========================
           Carousel Buttons
           ======================== */
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #16a34a;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .carousel-btn:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background-color: #f9f9f9;
        }

        .carousel-btn-prev {
            left: -20px;
        }

        .carousel-btn-next {
            right: -20px;
        }

        /* ========================
           Service Card
           ======================== */
        .service-card {
            width: 300px;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* ========================
           Service Image
           ======================== */
        .service-image-link {
            text-decoration: none;
            display: block;
        }

        .service-image-container {
            height: 160px;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
            background-color: #f5f5f5;
        }

        .service-image {
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .service-card:hover .service-image {
            transform: scale(1.05);
        }

        .image-placeholder {
            background-color: #f5f5f5;
        }

        .placeholder-icon {
            font-size: 2.5rem;
        }

        /* ========================
           Service Body
           ======================== */
        .service-body {
            display: flex;
            flex-direction: column;
            padding: 0.75rem;
            flex-grow: 1;
        }

        /* ========================
           Service Title
           ======================== */
        .service-title-link {
            text-decoration: none;
            color: inherit;
        }

        .service-title {
            font-size: 0.95rem;
            color: #1a2035;
            line-height: 1.4;
        }

        .service-title-link:hover .service-title {
            color: #16a34a;
        }

        /* ========================
           What's Included Section
           ======================== */
        .included-section {
            margin-bottom: 1rem;
        }

        .included-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .included-list {
            font-size: 0.82rem;
            line-height: 1.5;
        }

        .included-item {
            color: #666;
            margin-bottom: 0.5rem;
            display: flex;
            gap: 0.5rem;
            align-items: flex-start;
        }

        .checkmark {
            color: #16a34a;
            font-weight: bold;
            flex-shrink: 0;
            line-height: 1.5;
        }

        /* ========================
           Service Footer
           ======================== */
        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 0.75rem;
            border-top: 1px solid #f0f0f0;
        }

        .service-price {
            color: #16a34a;
            font-size: 0.9rem;
        }

        /* ========================
           Order Button
           ======================== */
        .btn-order {
            background-color: #16a34a;
            color: white;
            border-radius: 20px;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
            white-space: nowrap;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Remove hover effect on WhatsApp button - Keep same color */
        .btn-order:hover {
            background-color: #16a34a;
            color: white;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(22, 163, 74, 0.3);
        }

        .btn-order i {
            font-size: 0.9rem;
        }

        /* ========================
           Responsive Design
           ======================== */
        @media (max-width: 768px) {
            .carousel-btn {
                display: none !important;
            }

            .service-card {
                width: 280px;
            }

            .popular-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .popular-services {
                padding: 1.5rem 0;
            }

            .popular-title {
                font-size: 1.25rem;
            }

            .popular-title span {
                font-size: 1.2rem;
            }

            .service-card {
                width: 240px;
            }

            .service-image-container {
                height: 140px;
            }

            .placeholder-icon {
                font-size: 2rem;
            }

            .service-body {
                padding: 0.6rem;
            }
        }
    </style>

    <script>
        function scrollServices(amount) {
            const container = document.getElementById('servicesScrollContainer');
            if (container) {
                container.scrollBy({
                    left: amount,
                    behavior: 'smooth'
                });
            }
        }
    </script>
@endif