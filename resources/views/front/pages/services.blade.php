@extends('front.layouts.guest')
@section('content')

@php
    $categoryIcons = [
        'engineering' => 'bi-compass',
        'technical' => 'bi-compass',
        'graphic' => 'bi-palette',
        'design' => 'bi-palette',
        'construction' => 'bi-building',
        'works' => 'bi-building',
        'shopping' => 'bi-bag',
        'supplies' => 'bi-bag',
        'refreshment' => 'bi-cup-hot',
        'catering' => 'bi-cup-hot',
        'coffee' => 'bi-cup-hot',
        'software' => 'bi-code-slash',
        'development' => 'bi-code-slash',
        'web' => 'bi-code-slash',
        'electrical' => 'bi-lightning-charge',
        'electric' => 'bi-lightning-charge',
        'photography' => 'bi-camera',
        'photo' => 'bi-camera',
        'video' => 'bi-camera-reels',
        'writing' => 'bi-pencil-square',
        'content' => 'bi-file-text',
        'event' => 'bi-calendar-event',
        'support' => 'bi-headset',
    ];
@endphp

<section class="service-directory">
    <div class="container-xl">
        <header class="service-directory__header">
            <h1 class="service-directory__title">Services Marketplace</h1>
            <p class="service-directory__intro">
                Connect with trusted specialists and explore services for your projects, business and everyday needs.
            </p>
        </header>

        <div class="row g-3 g-lg-4">
            @forelse($categories as $category)
                @php
                    $categoryKeywords = \Illuminate\Support\Str::lower($category->title . ' ' . $category->slug);
                    $iconClass = 'bi-briefcase';

                    foreach ($categoryIcons as $keyword => $candidateIcon) {
                        if (\Illuminate\Support\Str::contains($categoryKeywords, $keyword)) {
                            $iconClass = $candidateIcon;
                            break;
                        }
                    }

                    $businessName = $category->getAttribute('business_name')
                        ?: $category->getAttribute('company_name');
                    $serviceCount = $category->services->count();
                    $categoryUrl = \Illuminate\Support\Str::slug($category->title) === 'online-shopping-supplies'
                        ? route('shop')
                        : route('services.category', $category->slug);
                @endphp

                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ $categoryUrl }}" class="service-category-card">
                        <div class="service-category-card__heading">
                            <span class="service-category-card__icon" aria-hidden="true">
                                <i class="bi {{ $iconClass }}"></i>
                            </span>
                            <div class="service-category-card__title-wrap">
                                <h2 class="service-category-card__title">{{ $category->title }}</h2>
                                @if($businessName)
                                    <p class="service-category-card__business">{{ $businessName }}</p>
                                @endif
                            </div>
                        </div>

                        <p class="service-category-card__description">
                            {{ $category->description
                                ? \Illuminate\Support\Str::limit($category->description, 140)
                                : 'Explore available services in this category.' }}
                        </p>

                        <div class="service-category-card__count">
                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                            <span>{{ $serviceCount }} {{ \Illuminate\Support\Str::plural('service', $serviceCount) }} available</span>
                        </div>

                        <div class="service-category-card__action">
                            <span>Explore Services</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="service-directory__empty">
                        <h2 class="service-directory__empty-title">No Services Available</h2>
                        <p class="mb-0">Please check back soon for new services.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</section>

<style>
    .service-directory {
        padding: 4rem 0 5rem;
        background: #fff;
    }

    .service-directory__header {
        margin-bottom: 2rem;
    }

    .service-directory__title {
        margin: 0 0 0.65rem;
        color: #111827;
        font-size: clamp(1.8rem, 3vw, 2.25rem);
        font-weight: 700;
        letter-spacing: -0.035em;
        line-height: 1.2;
    }

    .service-directory__intro {
        max-width: 42rem;
        margin: 0;
        color: #6b7280;
        font-size: 1rem;
        line-height: 1.65;
    }

    .service-category-card {
        display: flex;
        height: 100%;
        min-width: 0;
        flex-direction: column;
        padding: 1.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.625rem;
        background: #fff;
        color: inherit;
        text-decoration: none;
        transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    .service-category-card:hover {
        transform: translateY(-2px);
        border-color: #bbf7d0;
        box-shadow: 0 6px 16px rgba(17, 24, 39, 0.06);
        color: inherit;
    }

    .service-category-card:focus-visible {
        outline: 3px solid rgba(22, 163, 74, 0.35);
        outline-offset: 3px;
    }

    .service-category-card__heading {
        display: flex;
        min-width: 0;
        align-items: flex-start;
        gap: 0.875rem;
    }

    .service-category-card__icon {
        display: inline-flex;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 2.75rem;
        align-items: center;
        justify-content: center;
        border-radius: 0.5rem;
        background: #f0fdf4;
        color: #16a34a;
        font-size: 1.25rem;
    }

    .service-category-card__title-wrap {
        min-width: 0;
    }

    .service-category-card__title {
        margin: 0;
        color: #111827;
        font-size: 1.075rem;
        font-weight: 650;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .service-category-card__business {
        margin: 0.3rem 0 0;
        color: #16a34a;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .service-category-card__description {
        margin: 1rem 0 1.125rem;
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }

    .service-category-card__count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #6b7280;
        font-size: 0.8rem;
        line-height: 1.4;
    }

    .service-category-card__count i {
        color: #9ca3af;
        font-size: 0.9rem;
    }

    .service-category-card__action {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: auto;
        padding-top: 1.25rem;
        color: #15803d;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .service-category-card__action i {
        flex: 0 0 auto;
        transition: transform 180ms ease;
    }

    .service-category-card:hover .service-category-card__action i {
        transform: translateX(3px);
    }

    .service-directory__empty {
        padding: 3rem 1rem;
        color: #6b7280;
        text-align: center;
    }

    .service-directory__empty-title {
        margin: 0 0 0.5rem;
        color: #111827;
        font-size: 1.25rem;
        font-weight: 600;
    }

    @media (max-width: 575.98px) {
        .service-directory {
            padding: 2.75rem 0 3.5rem;
        }

        .service-directory__header {
            margin-bottom: 1.5rem;
        }

        .service-category-card {
            padding: 1.25rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .service-category-card,
        .service-category-card__action i {
            transition: none;
        }
    }
</style>

@endsection
