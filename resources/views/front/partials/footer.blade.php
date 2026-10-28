@php
    use App\Models\Service;

    $footerServices = Service::with('category')
        ->where('is_in_stock', true)
        ->latest()
        ->limit(5)
        ->get();
@endphp

<footer style="background: #1e293b; color: #e2e8f0; border-top: 1px solid #334155;">
    <div class="container-xl px-4 px-lg-5 py-5">

        <div class="row g-4">

            {{-- Brand --}}
            <div class="col-12 col-md-3">
                <h3 class="fw-bold mb-2" style="font-family:'Space Grotesk',sans-serif; color:#fff; font-size:1.15rem;">
                    TRESCA <span style="font-weight:300;">Hub</span>
                </h3>
                <p class="mb-1" style="font-size:0.875rem; color:#94a3b8; opacity:0.7;">
                    Your Link to Services
                </p>
                <p style="font-size:0.75rem; color:#94a3b8; opacity:0.5;">
                    Powered by TRESCA GROUP
                </p>
            </div>

            {{-- Services (dynamic) --}}
            <div class="col-6 col-md-3">
                <h4 class="text-uppercase fw-semibold mb-3"
                    style="font-size:12px; letter-spacing:0.08em; color:#94a3b8; opacity:0.7;">
                    Services
                </h4>
                <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:8px;">
                    @forelse($footerServices as $service)
                        <li>
                            <a href="{{ route('service.show', $service->slug) }}"
                               class="text-decoration-none footer-link"
                               style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">
                                {{ $service->title }}
                            </a>
                        </li>
                    @empty
                        <li style="color:#64748b; font-size:0.875rem;">No services yet</li>
                    @endforelse

                    <li class="mt-2">
                        <a href="{{ route('services') }}"
                           class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">
                            View all →
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Quick Links --}}
            <div class="col-6 col-md-3">
                <h4 class="text-uppercase fw-semibold mb-3"
                    style="font-size:12px; letter-spacing:0.08em; color:#94a3b8; opacity:0.7;">
                    Quick Links
                </h4>
                <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:8px;">
                    <li>
                        <a href="{{ route('home') }}" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">Home</a>
                    </li>
                    <li>
                        <a href="{{ route('shop') }}" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">Shop</a>
                    </li>
                    <li>
                        <a href="{{ route('for-partners') }}" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">For Partners</a>
                    </li>
                    <li>
                        <a href="{{ route('request-service') }}" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">Request a Service</a>
                    </li>
                    <li>
                        <a href="{{ route('services') }}" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">All Services</a>
                    </li>
                </ul>
            </div>

            {{-- Contact --}}
            <div class="col-12 col-md-3">
                <h4 class="text-uppercase fw-semibold mb-3"
                    style="font-size:12px; letter-spacing:0.08em; color:#94a3b8; opacity:0.7;">
                    Contact
                </h4>
                <ul class="list-unstyled mb-0" style="display:flex; flex-direction:column; gap:8px;">
                    <li style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">
                        📞 +250 786 908 916
                    </li>
                    <li>
                        <a href="mailto:info@trescahub.com" class="text-decoration-none footer-link"
                           style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">
                            📧 info@trescahub.com
                        </a>
                    </li>
                    <li style="color:#94a3b8; font-size:0.875rem; opacity:0.7;">
                        📍 Kigali, Rwanda
                    </li>
                </ul>
            </div>

        </div>

        {{-- Bottom Bar --}}
        <div class="mt-5 pt-4 text-center"
             style="border-top: 1px solid rgba(148,163,184,0.1);">
            <p class="mb-2" style="font-size:0.75rem; color:#94a3b8; opacity:0.5;">
                TRESCA Hub is not a separate registered company. It is a services coordination
                and ordering platform operated under the TRESCA GROUP umbrella.
            </p>
            <p class="mb-0" style="font-size:0.75rem; color:#94a3b8; opacity:0.5;">
                © {{ date('Y') }} TRESCA GROUP. All rights reserved.
            </p>
        </div>

    </div>

    {{-- Spacer for mobile bottom nav --}}
    <div class="d-lg-none" style="height: 64px;"></div>
</footer>

<style>
    .footer-link:hover {
        opacity: 1 !important;
        color: #fff !important;
    }
</style>