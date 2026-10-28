@extends('front.layouts.guest')
@section('content')

<main class="py-5 py-md-5" style="padding-top: 5rem !important; padding-bottom: 5rem !important;">
    <div class="container">

        {{-- Header --}}
        <div class="text-center mb-5">
            <h1 class="fw-bold mb-3" style="font-size: 2.8rem; color: #111; letter-spacing: -0.5px;">
                Services Marketplace
            </h1>
            <p class="mx-auto" style="max-width: 600px; font-size: 1.05rem; color: #6b7280; line-height: 1.7;">
                Browse our five specialized service stores. Order packages like products — add to cart, checkout, and track your order.
            </p>
        </div>

        {{-- Services Grid --}}
        <div class="row g-4 mt-4">
            @forelse($categories as $category)
            @php
                $emojiMap = [
                    'graphic'      => '🎨',
                    'design'       => '✏️',
                    'content'      => '📝',
                    'writing'      => '📝',
                    'web'          => '💻',
                    'development'  => '💻',
                    'photography'  => '📷',
                    'photo'        => '📷',
                    'video'        => '🎬',
                    'production'   => '🎬',
                    'engineering'  => '🔧',
                    'technical'    => '⚙️',
                    'construction' => '🏗️',
                    'shopping'     => '🛒',
                    'supplies'     => '📦',
                    'refreshment'  => '🥤',
                    'catering'     => '🍽️',
                    'event'        => '📅',
                    'support'      => '🆘',
                    'works'        => '🔨',
                ];

                $categoryLower = strtolower($category->title);
                $emoji = '⭐';
                foreach ($emojiMap as $keyword => $icon) {
                    if (str_contains($categoryLower, $keyword)) {
                        $emoji = $icon;
                        break;
                    }
                }

                $packageCount = $category->services()->count();
            @endphp

                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ route('services.category', $category->slug) }}"
                       class="text-decoration-none">
                        <div class="service-card h-100 d-flex flex-column p-4 rounded-4"
                             style="background: #fff;
                                    border: 1px solid #e5e7eb;
                                    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                                    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);">

                            {{-- Emoji Icon --}}
                            <span style="font-size: 3rem; line-height: 1; margin-bottom: 16px; display: block;">
                                {{ $emoji }}
                            </span>

                            {{-- Title --}}
                            <h2 class="fw-bold mb-1" style="font-size: 1.15rem; color: #111;">
                                {{ $category->title }}
                            </h2>

                            {{-- Company tag --}}
                            <p class="fw-semibold mb-2" style="font-size: 0.75rem; color: #16a34a; text-transform: uppercase; letter-spacing: 0.4px;">
                                TRESCA {{ strtoupper(explode(' ', trim($category->title))[0]) }} Ltd
                            </p>

                            {{-- Description --}}
                            <p class="mb-4" style="font-size: 0.875rem; color: #6b7280; line-height: 1.6; flex-grow: 1;">
                                {{ Str::limit($category->description, 100) }}
                            </p>

                            {{-- Browse Link --}}
                            <div class="service-link d-flex align-items-center gap-2 fw-semibold"
                                 style="font-size: 0.875rem; color: #16a34a; transition: gap 0.3s ease;">
                                <span>Browse {{ $packageCount }} {{ Str::plural('service', $packageCount) }}</span>
                                <span class="service-arrow" style="transition: transform 0.3s ease; display: inline-block;">→</span>
                            </div>

                        </div>
                    </a>
                </div>

            @empty
                <div class="col-12">
                    <div class="text-center py-5" style="color: #6b7280;">
                        <span style="font-size: 3rem;">📭</span>
                        <h5 class="mt-3 mb-1 fw-bold">No Services Available</h5>
                        <p class="mb-0">Please check back soon for new services.</p>
                    </div>
                </div>
            @endforelse
        </div>

    </div>
</main>

<style>
    .service-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1) !important;
    }
    .service-card:hover .service-link {
        gap: 0.75rem !important;
    }
    .service-card:hover .service-arrow {
        transform: translateX(4px);
    }

    @media (max-width: 576px) {
        h1 { font-size: 2rem !important; }
    }
</style>

@endsection