@extends('front.layouts.guest')
@section('content')

<main class="py-5" style="padding-top: 5rem !important; padding-bottom: 5rem !important; background-color: #f9f9f6;">
    <div class="container" style="max-width: 780px;">

        {{-- Header --}}
        <span style="font-size: 3.5rem; line-height: 1; display: block; margin-bottom: 16px;">🤝</span>

        <h1 class="fw-bold mb-3" style="font-size: 2.8rem; color: #111; letter-spacing: -0.5px; line-height: 1.2;">
            For Partners & Clients
        </h1>

        <p style="font-size: 1.1rem; color: #6b7280; line-height: 1.8; max-width: 680px; margin-bottom: 0;">
            Whether you're a corporate client, an NGO, or a fellow business — TRESCA Hub offers
            a streamlined way to access, order, and manage professional services.
        </p>

        {{-- Benefit Cards --}}
        <div class="d-flex flex-column mt-5" style="gap: 20px;">

            @foreach([
                ['title' => 'Dedicated Account Support',  'desc' => 'Get priority handling, direct communication, and tailored service delivery.'],
                ['title' => 'Bulk & Recurring Orders',     'desc' => 'Order at scale with custom pricing and flexible delivery schedules.'],
                ['title' => 'Transparent Tracking',        'desc' => 'Monitor all orders in one place with status updates and payment tracking.'],
                ['title' => 'Partnership Opportunities',   'desc' => 'Join the TRESCA GROUP ecosystem as a service provider or distribution partner.'],
            ] as $item)
            <div class="partner-card p-4 rounded-4"
                 style="background: #fff;
                        border: 1px solid #e5e7eb;
                        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);">
                <h5 class="fw-bold mb-2" style="color: #111; font-size: 1rem;">
                    {{ $item['title'] }}
                </h5>
                <p class="mb-0" style="color: #6b7280; font-size: 0.9rem; line-height: 1.6;">
                    {{ $item['desc'] }}
                </p>
            </div>
            @endforeach

        </div>

        {{-- Partners --}}
        @if(isset($partners) && $partners->count() > 0)
        <div class="mt-5 pt-5" style="border-top: 1px solid #e5e7eb;">
            <h2 class="fw-bold mb-4" style="color: #111;">Our Partners</h2>
            <div class="d-flex flex-column" style="gap: 20px;">
                @foreach($partners as $partner)
                <div class="partner-card p-4 rounded-4"
                     style="background: #fff;
                            border: 1px solid #e5e7eb;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                            transition: all 0.3s cubic-bezier(0.4,0,0.2,1);">
                    <h5 class="fw-bold mb-2" style="color: #111; font-size: 1rem;">
                        {{ $partner->title }}
                    </h5>
                    <p class="mb-0" style="color: #6b7280; font-size: 0.85rem; line-height: 1.6;
                                           display: -webkit-box; -webkit-line-clamp: 3;
                                           -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $partner->description }}
                    </p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- CTA --}}
        <div class="mt-5 text-center">
            <a href="{{ route('request-service') }}"
               class="btn btn-lg fw-semibold"
               style="background-color: #16a34a; color: #fff;
                      border: none; border-radius: 10px;
                      padding: 14px 36px; font-size: 1rem;
                      transition: all 0.2s ease;">
                Get in Touch
            </a>
        </div>

    </div>
</main>

<style>
    .partner-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
    }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }
    @media (max-width: 576px) {
        h1 { font-size: 2rem !important; }
    }
</style>

@endsection