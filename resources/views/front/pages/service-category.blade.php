@extends('front.layouts.guest')
@section('content')

<main class="py-12 py-md-20">
    <div class="container">
        {{-- Header Section --}}
        <div class="mb-5">
            <a href="{{ route('services') }}" class="btn btn-link text-decoration-none mb-3">
                <i class="fas fa-arrow-left"></i> Back to Services
            </a>
            
            <h1 class="display-4 fw-bold mb-4">{{ $category->title }}</h1>
            
            @if($category->description)
                <p class="fs-5 text-muted mx-auto" style="max-width: 800px;">
                    {{ $category->description }}
                </p>
            @endif
        </div>

        {{-- Services Grid --}}
        <div class="row g-4 mt-5">
            @forelse($category->services as $service)
                <div class="col-12 col-md-6 col-lg-4">
                    <a href="{{ route('services.show', $service->slug) }}" class="text-decoration-none">
                        <div class="card h-100 service-card"
                             style="border: none; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">
                            
                            {{-- Thumbnail Image --}}
                            @if($service->thumbnail)
                                <div class="service-thumbnail" style="overflow: hidden; height: 200px; background-color: #f3f4f6;">
                                    <img src="{{ asset('' . $service->thumbnail) }}" 
                                         alt="{{ $service->title }}"
                                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                                </div>
                            @endif
                            
                            <div class="card-body d-flex flex-column p-4">
                                {{-- Title --}}
                                <h2 class="card-title fw-bold fs-6 mb-2" style="color: var(--bs-body-color); min-height: 2.5rem;">
                                    {{ $service->title }}
                                </h2>

                                {{-- Description --}}
                                @if($service->description)
                                    <p class="card-text text-muted flex-grow-1 small mb-3" style="font-size: 0.875rem; line-height: 1.6;">
                                        {{ Str::limit($service->description, 80) }}
                                    </p>
                                @endif

                                {{-- What's Included Section --}}
                                <div class="mb-4">
                                    <p class="small fw-semibold text-uppercase" style="color: #6b7280; font-size: 0.75rem; letter-spacing: 0.5px; margin-bottom: 0.75rem;">
                                        What's Included
                                    </p>
                                    @php
                                        $includes = [
                                            'Service consultation',
                                            'Professional assessment',
                                            'Quality assurance',
                                            'Post-service support'
                                        ];
                                    @endphp
                                    @foreach($includes as $item)
                                        <div class="d-flex gap-2 mb-2">
                                            <i class="fas fa-check" style="color: var(--bs-primary-color); font-size: 0.75rem; margin-top: 0.25rem;"></i>
                                            <span class="text-muted" style="font-size: 0.875rem;">{{ $item }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Price and Action --}}
                                <div class="d-flex align-items-center justify-content-between pt-3" style="margin-top: auto;">
                                    <div>
                                        <div class="small fw-semibold text-muted" style="font-size: 0.75rem;">Starting at</div>
                                        <div class="text-primary fw-bold" style="font-size: 1.25rem;">
                                            RWF {{ number_format($service->price, 0, '.', ',') }}
                                        </div>
                                    </div>
                                    <a href="{{ route('service.show', $service->slug) }}" class="btn btn-sm d-flex align-items-center gap-2 px-3" style="background-color: var(--bs-primary-color); color: white; border-radius: 6px; text-decoration: none; white-space: nowrap; border: none;">
                                        <i class="fas fa-arrow-right" style="font-size: 0.75rem;"></i>
                                        Order
                                    </a>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center py-5">
                        <h5 class="mb-2">No Services Available</h5>
                        <p class="text-muted">This category doesn't have any services yet. Please check back soon!</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</main>

<style>
    :root {
        --bs-body-color: #1f2937;
        --bs-border-color: #e5e7eb;
        --bs-primary-color: #16a34a;
        --bs-text-muted: #6b7280;
    }

    .py-md-20 {
        padding-top: 5rem;
        padding-bottom: 5rem;
    }

    @media (max-width: 768px) {
        .py-md-20 {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
    }

    .service-card {
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background-color: #fff;
        overflow: hidden;
    }

    .service-card:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: translateY(-4px);
    }

    .service-card:hover .service-thumbnail img {
        transform: scale(1.05);
    }

    .service-card:hover .service-link {
        gap: 0.75rem;
    }

    .service-card:hover .fa-arrow-right {
        transform: translateX(4px);
    }

    .service-thumbnail {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .flex-grow-1 {
        flex-grow: 1 !important;
    }

    .text-primary {
        color: var(--bs-primary-color) !important;
    }

    .text-muted {
        color: var(--bs-text-muted) !important;
    }

    .display-4 {
        color: var(--bs-body-color);
        font-weight: 700;
    }

    .fs-5 {
        color: var(--bs-text-muted);
    }

    .btn-link {
        color: var(--bs-primary-color);
        font-weight: 500;
    }

    .btn-link:hover {
        text-decoration: underline !important;
    }

    /* Responsive */
    @media (max-width: 576px) {
        .display-4 {
            font-size: 2rem;
        }

        .fs-5 {
            font-size: 1rem !important;
        }
    }
</style>

@endsection