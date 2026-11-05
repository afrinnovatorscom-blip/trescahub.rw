@extends('front.layouts.guest')
@section('content')

<main class="py-5 partners-page" style="padding-top: 5rem !important; padding-bottom: 5rem !important; background-color: #f9f9f6;">
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
    .partners-page .partner-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
    }
    .partners-page .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    @media (max-width: 767.98px) {
        .partners-page {
            overflow-x: clip;
            padding-top: 3rem !important;
            padding-bottom: 3rem !important;
        }

        .partners-page > .container {
            padding-right: max(1rem, env(safe-area-inset-right));
            padding-left: max(1rem, env(safe-area-inset-left));
        }

        .partners-page h1 {
            max-width: 100%;
            font-size: clamp(2rem, 6vw, 2.45rem) !important;
            overflow-wrap: anywhere;
        }

        .partners-page > .container > p {
            font-size: 1rem !important;
            line-height: 1.65 !important;
            overflow-wrap: anywhere;
        }

        .partners-page .partner-card {
            min-width: 0;
            padding: 1.15rem !important;
            border-radius: 12px !important;
        }

        .partners-page .partner-card h5,
        .partners-page .partner-card p {
            overflow-wrap: anywhere;
        }

        .partners-page .partner-card h5 {
            font-size: .98rem !important;
        }

        .partners-page .partner-card p {
            font-size: .88rem !important;
        }

        .partners-page .btn {
            min-height: 48px;
            padding: 13px 28px !important;
        }
    }

    @media (max-width: 575.98px) {
        .partners-page {
            padding-top: 2.25rem !important;
            padding-bottom: 2.5rem !important;
        }

        .partners-page > .container > span {
            font-size: 2.8rem !important;
            margin-bottom: 12px !important;
        }

        .partners-page h1 {
            margin-bottom: .75rem !important;
            font-size: clamp(1.8rem, 8vw, 2.15rem) !important;
            line-height: 1.15 !important;
            letter-spacing: -.35px !important;
        }

        .partners-page .partner-card {
            padding: 1rem !important;
        }

        .partners-page .mt-5 {
            margin-top: 1.75rem !important;
        }

        .partners-page .btn {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
        }
    }
</style>

@endsection