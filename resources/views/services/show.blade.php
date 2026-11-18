@extends('front.layouts.guest')
@section('content')
@php
    $bookingCustomer = auth('customers')->user();
    $serviceImageUrl = function ($path) {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', ltrim(trim($path), '/'));
        $path = preg_replace('#^public/#', '', $path);

        if ($path === '' || preg_match('#(^|/)\.\.(/|$)|^[a-z][a-z0-9+.-]*:#i', $path)) {
            return null;
        }

        return is_file(public_path($path)) ? asset($path) : null;
    };
    $mainServiceImageUrl = $serviceImageUrl($service->thumbnail);
    $galleryImageUrls = collect($service->other_images ?? [])
        ->map($serviceImageUrl)
        ->filter()
        ->values();
@endphp

<div class="container py-5">
    @if(session('booking_reference'))
        <div class="alert alert-success alert-dismissible fade show" role="status">
            <i class="bi bi-check-circle me-2" aria-hidden="true"></i>
            Booking submitted successfully. Your reference is <strong>{{ session('booking_reference') }}</strong>.
            The date and time requested are preferences; the Admin team will contact you to confirm the appointment.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Back Button --}}
    <a href="{{ route('services.category', $service->category->slug) }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-muted mb-4" style="font-size: 0.95rem;">
        <i class="bi bi-arrow-left"></i>
        Back to {{ $service->category->title }}
    </a>

    <div class="row g-4 g-lg-5 align-items-start service-detail-top">
        {{-- Service Images --}}
        <div class="col-lg-6 service-gallery-column">
            <div class="service-images">
                {{-- Main Image --}}
                @if($mainServiceImageUrl || $galleryImageUrls->isNotEmpty())
                    <div class="main-image service-main-image" style="display: {{ $mainServiceImageUrl ? 'block' : 'none' }};">
                        <img id="mainImage"
                             @if($mainServiceImageUrl) src="{{ $mainServiceImageUrl }}" @endif
                             alt="{{ $service->title }}"
                             onerror="this.closest('.main-image').style.display='none'"
                             style="display: block; width: 100%; height: 100%; object-fit: cover;">
                    </div>
                @endif

                {{-- Gallery Thumbnails --}}
                @if($mainServiceImageUrl || $galleryImageUrls->isNotEmpty())
                    <div class="gallery-thumbnails service-gallery-thumbnails">
                        @if($mainServiceImageUrl)
                            <div class="gallery-item active service-gallery-item" data-image-src="{{ $mainServiceImageUrl }}" onclick="changeImage(this)">
                                <img src="{{ $mainServiceImageUrl }}" alt="Main" onerror="this.closest('.gallery-item').remove(); const gallery = document.querySelector('.gallery-thumbnails'); if (gallery && !gallery.querySelector('.gallery-item')) gallery.remove()">
                            </div>
                        @endif
                        @foreach($galleryImageUrls as $galleryImageUrl)
                            <div class="gallery-item service-gallery-item" data-image-src="{{ $galleryImageUrl }}" onclick="changeImage(this)">
                                <img src="{{ $galleryImageUrl }}" alt="Gallery" onerror="this.closest('.gallery-item').remove(); const gallery = document.querySelector('.gallery-thumbnails'); if (gallery && !gallery.querySelector('.gallery-item')) gallery.remove()">
                            </div>
                        @endforeach
                    </div>
                @endif
                <script>
                    function changeImage(item) {
                        const mainImage = document.getElementById('mainImage');
                        if (!mainImage) {
                            return;
                        }

                        document.querySelectorAll('.gallery-item').forEach(galleryItem => galleryItem.style.borderColor = '#e5e7eb');
                        item.style.borderColor = '#16a34a';

                        mainImage.closest('.main-image').style.display = 'block';
                        mainImage.src = item.dataset.imageSrc;
                    }
                </script>

                {{-- Provider Info --}}
                @if($service->category)
                    <div class="service-provider">
                        <p class="service-provider-label">Provided by</p>
                        <p class="service-provider-name">
                            TRESCA {{ $service->category->title }}
                        </p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Service Details --}}
        <div class="col-lg-6 service-detail-info">
            {{-- Category Badge --}}
            @if($service->category)
                <div class="service-category-wrap">
                    <span class="service-category-label">
                        {{ $service->category->title }}
                    </span>
                </div>
            @endif

            {{-- Title --}}
            <h1 class="service-detail-title">
                {{ $service->title }}
            </h1>

            {{-- Description --}}
            @if($service->description)
                <p class="service-detail-description">
                    {{ $service->description }}
                </p>
            @endif

            {{-- Price & Turnaround Box --}}
            <div class="service-price-panel">
                <div class="service-price-main">
                    <span class="service-price-label">Starting Price</span>
                    <h2 class="service-price-value">
                        From RWF {{ number_format($service->price, 0, '.', ',') }}
                    </h2>
                </div>

                @if($service->working_day)
                    <p class="service-turnaround">
                        <i class="bi bi-clock" aria-hidden="true"></i>
                        <span>{{ $service->working_day }} working days</span>
                    </p>
                @endif

                <div class="service-order-actions">
                    <a href="https://wa.me/250780290040" target="_blank" rel="noopener noreferrer" class="service-order-button">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> Order via WhatsApp
                    </a>
                    @if($bookingCustomer)
                        <button type="button" class="service-booking-button" data-bs-toggle="modal" data-bs-target="#serviceBookingModal">
                            <i class="bi bi-calendar2-check" aria-hidden="true"></i> Order Now
                        </button>
                    @else
                        <a href="{{ route('service.show', ['service' => $service->slug, 'auth_required' => 1, 'booking_after_login' => 1]) }}" class="service-booking-button">
                            <i class="bi bi-calendar2-check" aria-hidden="true"></i> Order Now
                        </a>
                    @endif
                </div>
            </div>

            {{-- What's Included --}}
            @if($service->what_included && count($service->what_included) > 0)
                <div class="service-included">
                    <h2 class="service-section-title">What's Included</h2>
                    <div class="service-included-grid">
                        @foreach($service->what_included as $item)
                            <div class="service-included-item">
                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                                <span>{{ $item }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- How Service Works --}}
    @if($service->how_service_works && count($service->how_service_works) > 0)
        <section class="service-process-panel" aria-labelledby="service-process-title">
            <div class="service-process-content">
                <h2 id="service-process-title" class="service-section-title">How This Service Works</h2>
                <div class="service-process-steps">
                    @foreach($service->how_service_works as $index => $step)
                        <div class="service-process-step">
                            <div class="service-process-number">
                                {{ $index + 1 }}
                            </div>
                            <p>{{ $step }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- WhatsApp Question Button --}}
            <div class="service-process-action">
                <a href="https://wa.me/250780290040" target="_blank" class="service-question-button">
                    <i class="bi bi-chat-dots me-2"></i> Ask a Question on WhatsApp
                </a>
            </div>
        </section>
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
                        <div class="card h-100 shadow-sm" data-service-url="{{ route('service.show', $related->slug) }}" role="link" tabindex="0" onclick="if (!event.target.closest('a, button')) window.location.assign(this.dataset.serviceUrl)" onkeydown="if ((event.key === 'Enter' || event.key === ' ') && !event.target.closest('a, button')) { event.preventDefault(); window.location.assign(this.dataset.serviceUrl); }" style="border: none; border-radius: 12px; overflow: hidden; transition: all 0.3s ease; cursor: pointer;">
                            {{-- Image --}}
                            @php $relatedImageUrl = $serviceImageUrl($related->thumbnail); @endphp
                            @if($relatedImageUrl)
                                <div data-service-image-card style="overflow: hidden; height: 180px; background-color: #f3f4f6;">
                                    <img src="{{ $relatedImageUrl }}"
                                         alt="{{ $related->title }}"
                                         onerror="this.closest('[data-service-image-card]').remove()"
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

<div class="modal fade booking-modal" id="serviceBookingModal" tabindex="-1" aria-labelledby="serviceBookingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('service-bookings.store', $service->slug) }}" data-service-booking-form>
                @csrf
                <input type="hidden" name="submission_key" value="{{ old('submission_key', (string) \Illuminate\Support\Str::uuid()) }}">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="serviceBookingModalLabel">Book This Service</h2>
                        <p class="booking-modal-description mb-0">Choose a preferred date. We’ll contact you to confirm the appointment.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close booking form"></button>
                </div>
                <div class="modal-body">
                    <div class="booking-form-grid">
                        <div class="booking-field booking-field-full">
                            <label for="booking-service">Service</label>
                            <input id="booking-service" class="form-control" type="text" value="{{ $service->title }}" readonly>
                        </div>
                        <div class="booking-field">
                            <label for="booking-preferred-date">Preferred Date <span aria-hidden="true">*</span></label>
                            <input id="booking-preferred-date" class="form-control @error('preferred_date') is-invalid @enderror" type="date" name="preferred_date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date') }}" required>
                            @error('preferred_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="booking-field">
                            <label for="booking-customer-name">Full Name <span aria-hidden="true">*</span></label>
                            <input id="booking-customer-name" class="form-control @error('customer_name') is-invalid @enderror" type="text" name="customer_name" maxlength="255" value="{{ old('customer_name', $bookingCustomer?->name) }}" autocomplete="name" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="booking-field">
                            <label for="booking-customer-phone">Phone Number <span aria-hidden="true">*</span></label>
                            <input id="booking-customer-phone" class="form-control @error('customer_phone') is-invalid @enderror" type="tel" name="customer_phone" maxlength="50" value="{{ old('customer_phone', $bookingCustomer?->phone) }}" autocomplete="tel" required>
                            @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="booking-field booking-field-full">
                            <label for="booking-customer-email">Email</label>
                            <input id="booking-customer-email" class="form-control @error('customer_email') is-invalid @enderror" type="email" name="customer_email" maxlength="255" value="{{ old('customer_email', $bookingCustomer?->email) }}" autocomplete="email">
                            @error('customer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="booking-field booking-field-full">
                            <label for="booking-requirements">Requirements <span class="text-muted fw-normal">(optional)</span></label>
                            <textarea id="booking-requirements" class="form-control @error('requirements') is-invalid @enderror" name="requirements" rows="3" maxlength="5000" placeholder="Tell us briefly what you need">{{ old('requirements') }}</textarea>
                            @error('requirements')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="booking-submit-button">
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        <span>Submit Booking</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .service-detail-top {
        row-gap: 2rem;
    }

    .service-gallery-column,
    .service-detail-info {
        min-width: 0;
    }

    .service-main-image {
        width: min(100%, 520px);
        aspect-ratio: 1 / 1;
        margin: 0 auto 1rem;
        overflow: hidden;
        border-radius: 12px;
        background: #f3f4f6;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }

    .service-gallery-thumbnails {
        display: flex;
        flex-wrap: wrap;
        gap: 0.625rem;
        width: min(100%, 520px);
        margin: 0 auto;
    }

    .service-gallery-item {
        flex: 0 0 80px;
        width: 80px;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .service-gallery-item.active {
        border-color: #16a34a;
    }

    .service-gallery-item:hover,
    .service-gallery-item:focus-visible {
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.14);
        outline: none;
    }

    .service-gallery-item img {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .service-provider {
        width: min(100%, 520px);
        margin: 1rem auto 0;
        padding: 0.875rem 1rem;
        border: 1px solid #dcfce7;
        border-radius: 8px;
        background: #f0fdf4;
    }

    .service-provider-label,
    .service-provider-name {
        margin: 0;
    }

    .service-provider-label {
        margin-bottom: 0.2rem;
        color: #6b7280;
        font-size: 0.75rem;
    }

    .service-provider-name {
        color: #15803d;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .service-category-wrap {
        margin-bottom: 0.75rem;
    }

    .service-category-label {
        display: inline-flex;
        align-items: center;
        min-height: 28px;
        padding: 0.3rem 0.65rem;
        border-radius: 6px;
        background: #f0fdf4;
        color: #15803d;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .service-detail-title {
        margin: 0 0 0.75rem;
        color: #1f2937;
        font-size: clamp(1.5rem, 2.8vw, 1.9rem);
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.02em;
        overflow-wrap: anywhere;
    }

    .service-detail-description {
        margin: 0 0 1.25rem;
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.65;
    }

    .service-price-panel {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 0.75rem 1rem;
        margin-bottom: 1.5rem;
        padding: 1.125rem;
        border: 1px solid #dcfce7;
        border-radius: 10px;
        background: #f0fdf4;
    }

    .service-price-label {
        display: block;
        margin-bottom: 0.2rem;
        color: #6b7280;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .service-price-value {
        margin: 0;
        color: #15803d;
        font-size: clamp(1.2rem, 2vw, 1.45rem);
        font-weight: 700;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }

    .service-turnaround {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        margin: 0;
        color: #4b5563;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .service-turnaround i {
        color: #15803d;
    }

    .service-order-button,
    .service-booking-button,
    .service-question-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-height: 40px;
        padding: 0.55rem 1rem;
        border: 1px solid #15803d;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        text-align: center;
        text-decoration: none;
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
    }

    .service-order-button {
        flex: 1 1 0;
        background: #15803d;
        color: #fff;
    }

    .service-order-actions {
        display: flex;
        grid-column: 1 / -1;
        gap: 0.75rem;
        min-width: 0;
    }

    .service-booking-button {
        flex: 1 1 0;
        background: #fff;
        color: #15803d;
        cursor: pointer;
    }

    .service-booking-button:hover {
        border-color: #166534;
        background: #f0fdf4;
        color: #166534;
        box-shadow: 0 2px 8px rgba(21, 128, 61, 0.12);
    }

    .service-order-button:hover {
        border-color: #166534;
        background: #166534;
        color: #fff;
    }

    .service-order-button:focus-visible,
    .service-booking-button:focus-visible,
    .service-question-button:focus-visible {
        outline: 3px solid rgba(22, 163, 74, 0.28);
        outline-offset: 2px;
    }

    .service-included {
        margin-bottom: 1.25rem;
    }

    .service-section-title {
        margin: 0 0 0.75rem;
        color: #1f2937;
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .service-included-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.625rem;
    }

    .service-included-item {
        display: flex;
        align-items: flex-start;
        gap: 0.55rem;
        min-width: 0;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        color: #4b5563;
        font-size: 0.85rem;
        line-height: 1.45;
        overflow-wrap: anywhere;
    }

    .service-included-item i {
        flex: 0 0 auto;
        margin-top: 0.12rem;
        color: #16a34a;
        font-size: 0.9rem;
    }

    .service-process-panel {
        margin-top: 2rem;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #f9fafb;
    }

    .service-process-content {
        padding: 1.25rem;
    }

    .service-process-steps {
        display: grid;
        gap: 0.65rem;
    }

    .service-process-step {
        display: grid;
        grid-template-columns: 32px minmax(0, 1fr);
        align-items: start;
        gap: 0.75rem;
        min-width: 0;
        padding: 0.8rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
    }

    .service-process-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #16a34a;
        color: #fff;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .service-process-step p {
        align-self: center;
        margin: 0;
        color: #4b5563;
        font-size: 0.875rem;
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .service-process-action {
        padding: 0.875rem 1.25rem;
        border-top: 1px solid #e5e7eb;
    }

    .service-question-button {
        background: #fff;
        color: #15803d;
    }

    .service-question-button:hover {
        border-color: #15803d;
        background: #f0fdf4;
        color: #166534;
    }

    .booking-modal .modal-content {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
    }

    .booking-modal .modal-header,
    .booking-modal .modal-footer {
        padding: 1.25rem 1.5rem;
        border-color: #e2e8f0;
    }

    .booking-modal .modal-title {
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
    }

    .booking-modal-description {
        margin-top: 0.25rem;
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .booking-modal .modal-body {
        padding: 1.5rem;
    }

    .booking-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .booking-field {
        min-width: 0;
    }

    .booking-field-full {
        grid-column: 1 / -1;
    }

    .booking-field label {
        display: block;
        margin-bottom: 0.4rem;
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .booking-field .form-control,
    .booking-field .form-select {
        min-height: 42px;
        border-color: #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        font-size: 0.9rem;
    }

    .booking-field textarea.form-control {
        min-height: 96px;
        resize: vertical;
    }

    .booking-field .form-control:focus,
    .booking-field .form-select:focus {
        border-color: #16a34a;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15);
    }

    .booking-submit-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        min-height: 42px;
        padding: 0.6rem 1rem;
        border: 1px solid #15803d;
        border-radius: 8px;
        background: #15803d;
        color: #fff;
        font-size: 0.875rem;
        font-weight: 600;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .booking-submit-button:hover {
        border-color: #166534;
        background: #166534;
    }

    .booking-submit-button:focus-visible {
        outline: 3px solid rgba(22, 163, 74, 0.28);
        outline-offset: 2px;
    }

    @media (max-width: 767.98px) {
        .service-detail-top {
            row-gap: 1.5rem;
        }

        .service-detail-info {
            padding-top: 0.25rem;
        }
    }

    @media (max-width: 575.98px) {
        .service-included-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .service-price-panel {
            grid-template-columns: minmax(0, 1fr);
        }

        .service-turnaround {
            grid-row: 2;
        }

        .service-order-button,
        .service-booking-button,
        .service-question-button {
            width: 100%;
        }

        .service-order-actions {
            flex-direction: column;
        }

        .booking-modal .modal-dialog {
            margin: 0.75rem;
        }

        .booking-modal .modal-header,
        .booking-modal .modal-body,
        .booking-modal .modal-footer {
            padding: 1rem;
        }

        .booking-form-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 0.875rem;
        }

        .booking-field-full {
            grid-column: auto;
        }

        .booking-modal .modal-footer {
            flex-wrap: wrap;
        }

        .booking-modal .modal-footer > button {
            flex: 1 1 auto;
        }

        .service-process-content {
            padding: 1rem;
        }

        .service-process-action {
            padding: 0.75rem 1rem;
        }
    }

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

@push('scripts')
<script>
document.querySelectorAll('[data-service-booking-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.querySelector('span').textContent = 'Submitting...';
        }
    });
});

@if($bookingCustomer && request()->boolean('auto_book'))
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('serviceBookingModal');
    if (modal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
        window.history.replaceState({}, '', @js(route('service.show', $service->slug)));
    }
});
@endif

@if($errors->any())
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('serviceBookingModal');
    if (modal && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
});
@endif
</script>
@endpush

@endsection