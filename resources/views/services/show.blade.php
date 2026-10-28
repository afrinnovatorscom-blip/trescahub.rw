@extends('front.layouts.guest')
@section('content')

<div class="container py-5">
    {{-- Back Button --}}
    <a href="{{ route('services.category', $service->category->slug) }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-muted mb-4" style="font-size: 0.95rem;">
        <i class="bi bi-arrow-left"></i>
        Back to {{ $service->category->title }}
    </a>

    <div class="row g-5">
        {{-- Service Images --}}
        <div class="col-lg-6 mb-4">
            @if($service->thumbnail)
                <div class="service-images">
                    {{-- Main Image --}}
                    <div class="main-image mb-3" style="border-radius: 12px; overflow: hidden; background-color: #f3f4f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <img id="mainImage" 
                             src="{{ asset('' . $service->thumbnail) }}" 
                             alt="{{ $service->title }}"
                             style="width: 100%; height: 400px; object-fit: cover;">
                    </div>

                    {{-- Gallery Thumbnails --}}
                    @if($service->other_images && count($service->other_images) > 0)
                        <div class="gallery-thumbnails" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px;">
                            <div class="gallery-item active" onclick="changeImage('{{ asset('public/' . $service->thumbnail) }}')" style="cursor: pointer; border: 2px solid #16a34a; border-radius: 8px; overflow: hidden; transition: all 0.3s ease;">
                                <img src="{{ asset('' . $service->thumbnail) }}" alt="Main" style="width: 100%; height: 80px; object-fit: cover;">
                            </div>
                            @foreach($service->other_images as $image)
                                <div class="gallery-item" onclick="changeImage('{{ asset('public/' . $image) }}')" style="cursor: pointer; border: 2px solid #e5e7eb; border-radius: 8px; overflow: hidden; transition: all 0.3s ease;">
                                    <img src="{{ asset('public' . $image) }}" alt="Gallery" style="width: 100%; height: 80px; object-fit: cover;">
                                </div>
                            @endforeach
                        </div>
                        <script>
                            function changeImage(src) {
                                document.getElementById('mainImage').src = src;
                                document.querySelectorAll('.gallery-item').forEach(item => item.style.borderColor = '#e5e7eb');
                                event.currentTarget.style.borderColor = '#16a34a';
                            }
                        </script>
                    @endif

                    {{-- Provider Info --}}
                    @if($service->category)
                        <div class="mt-4 p-3 rounded-2" style="background-color: #f0fdf4;">
                            <p class="text-muted small mb-1" style="font-size: 0.8rem;">Provided by</p>
                            <p class="fw-bold mb-0" style="color: #16a34a; font-size: 0.95rem;">
                                TRESCA {{ $service->category->title }}
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Service Details --}}
        <div class="col-lg-6">
            {{-- Category Badge --}}
            @if($service->category)
                <div class="mb-3">
                    <span class="badge" style="background-color: #d1fae5; color: #16a34a; font-size: 0.8rem; padding: 0.45rem 0.75rem; border-radius: 20px;">
                        {{ $service->category->title }}
                    </span>
                </div>
            @endif

            {{-- Title --}}
            <h1 class="mb-3" style="font-size: 2rem; font-weight: 700; color: #1f2937; line-height: 1.2;">
                {{ $service->title }}
            </h1>

            {{-- Description --}}
            @if($service->description)
                <p class="text-muted mb-4" style="line-height: 1.6; font-size: 0.95rem; color: #6b7280;">
                    {{ $service->description }}
                </p>
            @endif

            {{-- Price & Turnaround Box --}}
            <div class="mb-4 p-4 rounded-3" style="background-color: #f0fdf4; border: 1px solid #dcfce7;">
                <div class="mb-3">
                    <span class="text-muted" style="font-size: 0.8rem; color: #6b7280;">Starting Price</span>
                    <h3 class="fw-bold mb-0" style="font-size: 1.75rem; color: #16a34a;">
                        From RWF {{ number_format($service->price, 0, '.', ',') }}
                    </h3>
                </div>

                @if($service->working_day)
                    <p class="text-muted small mb-0" style="font-size: 0.9rem;">
                        <i class="bi bi-clock" style="color: #16a34a; margin-right: 0.5rem;"></i> 
                        <strong>{{ $service->working_day }} working days</strong>
                    </p>
                @endif

                {{-- Order Button --}}
                <div class="mt-4">
                    <a href="https://wa.me/250780290040" target="_blank" class="btn btn-lg w-50" style="background-color: #16a34a; border: none; color: white; font-weight: 600;">
                        <i class="bi bi-whatsapp me-2"></i> Order via WhatsApp
                    </a>
                </div>
            </div>

            {{-- What's Included --}}
            @if($service->what_included && count($service->what_included) > 0)
                <div class="mb-4">
                    <h5 class="fw-bold mb-3" style="color: #1f2937; font-size: 1rem;">What's Included</h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        @foreach($service->what_included as $item)
                            <div style="background-color: #f3f4f6; padding: 12px 14px; border-radius: 8px; display: flex; gap: 10px; align-items: flex-start;">
                                <i class="bi bi-check-circle-fill" style="color: #16a34a; flex-shrink: 0; font-size: 1rem; margin-top: 2px;"></i>
                                <span class="text-muted" style="font-size: 0.9rem; line-height: 1.4;">{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- How Service Works --}}
    @if($service->how_service_works && count($service->how_service_works) > 0)
        <div class="mt-5 rounded-3" style="background-color: #f9fafb; border: 1px solid #e5e7eb; padding: 0;">
            <div style="padding: 28px;">
                <h5 class="fw-bold mb-4" style="color: #1f2937; font-size: 1rem;">How This Service Works</h5>
                <div style="display: flex; flex-direction: column;">
                    @foreach($service->how_service_works as $index => $step)
                        <div style="display: flex; gap: 16px; padding: 16px 0; @if($index < count($service->how_service_works) - 1) border-bottom: 1px solid #e5e7eb; @endif">
                            <div style="min-width: 28px; width: 28px; height: 28px; background-color: #16a34a; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; flex-shrink: 0; font-size: 0.85rem;">
                                {{ $index + 1 }}
                            </div>
                            <p class="text-muted mb-0" style="font-size: 0.9rem; color: #6b7280; line-height: 1.5; display: flex; align-items: center;">{{ $step }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- WhatsApp Question Button --}}
            <div style="padding: 16px 28px; border-top: 1px solid #e5e7eb;">
                <a href="https://wa.me/250780290040" target="_blank" class="btn w-100" style="border: 1px solid #d1d5db; color: #6b7280; background-color: white;">
                    <i class="bi bi-chat-dots me-2"></i> Ask a Question on WhatsApp
                </a>
            </div>
        </div>
    @endif

    {{-- Related Services --}}
    @if($relatedServices && count($relatedServices) > 0)
        <div class="mt-5 pt-5 border-top">
            <h3 class="fw-bold mb-4" style="color: #1f2937;">
                More from {{ $service->category->title }}
            </h3>
            <div class="row g-4">
                @foreach($relatedServices as $related)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm" style="border: none; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; cursor: pointer;">
                            {{-- Image --}}
                            @if($related->thumbnail)
                                <div style="overflow: hidden; height: 180px; background-color: #f3f4f6;">
                                    <img src="{{ asset('' . $related->thumbnail) }}" 
                                         alt="{{ $related->title }}"
                                         style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                                </div>
                            @endif

                            <div class="card-body d-flex flex-column p-4">
                                {{-- Title --}}
                                <h6 class="card-title fw-bold mb-2" style="color: #1f2937; font-size: 0.95rem;">
                                    {{ $related->title }}
                                </h6>

                                {{-- Description --}}
                                @if($related->description)
                                    <p class="text-muted small mb-3" style="flex-grow: 1; line-height: 1.5; font-size: 0.85rem;">
                                        {{ Str::limit($related->description, 60) }}
                                    </p>
                                @endif

                                {{-- What's Included --}}
                                @if($related->what_included && count($related->what_included) > 0)
                                    <div class="mb-3">
                                        <p class="text-uppercase small fw-semibold text-muted mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">What's Included</p>
                                        <ul class="list-unstyled" style="font-size: 0.8rem;">
                                            @foreach($related->what_included as $item)
                                                <li class="mb-1">
                                                    <i class="bi bi-check" style="color: #16a34a; margin-right: 0.5rem;"></i>{{ $item }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                {{-- Price & Button --}}
                                <div class="mt-auto pt-3" style="border-top: 1px solid #e5e7eb;">
                                    <div class="mb-3">
                                        <p class="fw-bold mb-0" style="font-size: 1rem; color: #16a34a;">
                                            From RWF {{ number_format($related->price, 0, '.', ',') }}
                                        </p>
                                        @if($related->working_day)
                                            <p class="text-muted small mb-0" style="font-size: 0.8rem;">{{ $related->working_day }} working days</p>
                                        @endif
                                    </div>
                                    <a href="{{ route('service.show', $related->slug) }}" class="btn btn-sm w-100" style="background-color: #16a34a; border: none; color: white;">
                                        <i class="bi bi-eye me-1"></i> View
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<style>
    .card:hover {
        box-shadow: 0 10px 15px rgba(22, 163, 74, 0.15) !important;
        transform: translateY(-4px);
    }

    .card:hover img {
        transform: scale(1.05);
    }

    .gallery-item:hover {
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15);
    }
</style>

@endsection