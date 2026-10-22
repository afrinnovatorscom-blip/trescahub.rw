@extends('front.layouts.admin')

@section('page-title', 'Dashboard')

@section('content')

@php
    use App\Models\Service;
    use App\Models\ShopProduct;
    use App\Models\Servicerequest;
    use App\Models\Testimonial;
    use App\Models\Partner;

    $totalServices        = Service::count();
    $totalProducts        = ShopProduct::count();
    $totalRequests        = ServiceRequest::count();
    $pendingRequests      = ServiceRequest::where('status', 'pending')->count();
    $reviewedRequests     = ServiceRequest::where('status', 'reviewed')->count();
    $completedRequests    = ServiceRequest::where('status', 'completed')->count();
    $pendingTestimonials  = Testimonial::where('status', 'pending')->count();
    $totalPartners        = Partner::count();

    $recentRequests = ServiceRequest::latest()->limit(6)->get();
    $recentServices = Service::with('category')->latest()->limit(5)->get();
        @endphp

        @include('marketplace.dashboard-cards')

{{-- ── Stat Cards ── --}}
<div class="row g-3 mb-4">

    {{-- Total Services --}}
    <div class="col-6 col-lg-3">
        <div class="dash-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(0,212,170,0.12);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-briefcase" style="color:#00d4aa;font-size:1.1rem;"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(0,212,170,0.1);color:#00d4aa;font-size:0.68rem;">Services</span>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:700;color:#1a2035;line-height:1;">
                {{ $totalServices }}
            </div>
            <div style="font-size:0.8rem;color:#7a8499;margin-top:4px;">Total Services</div>
            <a href="{{ route('services.index') }}" style="font-size:0.75rem;color:#00d4aa;text-decoration:none;margin-top:8px;display:inline-block;">View all →</a>
        </div>
    </div>

    {{-- Total Products --}}
    <div class="col-6 col-lg-3">
        <div class="dash-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(246,119,35,0.12);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-box-seam" style="color:#F67723;font-size:1.1rem;"></i>
                </div>
                <span class="badge rounded-pill" style="background:rgba(246,119,35,0.1);color:#F67723;font-size:0.68rem;">Shop</span>
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:700;color:#1a2035;line-height:1;">
                {{ $totalProducts }}
            </div>
            <div style="font-size:0.8rem;color:#7a8499;margin-top:4px;">Shop Products</div>
            <a href="{{ route('admin.shop-products.index') }}" style="font-size:0.75rem;color:#F67723;text-decoration:none;margin-top:8px;display:inline-block;">View all →</a>
        </div>
    </div>

    {{-- Service Requests --}}
    <div class="col-6 col-lg-3">
        <div class="dash-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-inbox" style="color:#6366f1;font-size:1.1rem;"></i>
                </div>
                @if($pendingRequests > 0)
                    <span class="badge rounded-pill" style="background:#ff4757;color:#fff;font-size:0.68rem;">{{ $pendingRequests }} pending</span>
                @endif
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:700;color:#1a2035;line-height:1;">
                {{ $totalRequests }}
            </div>
            <div style="font-size:0.8rem;color:#7a8499;margin-top:4px;">Service Requests</div>
            <a href="{{ route('service-requests.index') }}" style="font-size:0.75rem;color:#6366f1;text-decoration:none;margin-top:8px;display:inline-block;">Manage →</a>
        </div>
    </div>

    {{-- Testimonials --}}
    <div class="col-6 col-lg-3">
        <div class="dash-card p-4 h-100">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div style="width:42px;height:42px;border-radius:12px;background:rgba(251,191,36,0.12);display:flex;align-items:center;justify-content:center;">
                    <i class="bi bi-chat-quote" style="color:#f59e0b;font-size:1.1rem;"></i>
                </div>
                @if($pendingTestimonials > 0)
                    <span class="badge rounded-pill" style="background:rgba(251,191,36,0.15);color:#f59e0b;font-size:0.68rem;">{{ $pendingTestimonials }} pending</span>
                @endif
            </div>
            <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:700;color:#1a2035;line-height:1;">
                {{ $pendingTestimonials }}
            </div>
            <div style="font-size:0.8rem;color:#7a8499;margin-top:4px;">Pending Reviews</div>
            <a href="{{ route('testimonials.index') }}" style="font-size:0.75rem;color:#f59e0b;text-decoration:none;margin-top:8px;display:inline-block;">Review →</a>
        </div>
    </div>

</div>

{{-- ── Second Row ── --}}
<div class="row g-3 mb-4">

    {{-- Request Status Breakdown --}}
    <div class="col-12 col-lg-4">
        <div class="dash-card p-4 h-100">
            <h6 style="font-family:'Syne',sans-serif;font-weight:700;color:#1a2035;margin-bottom:20px;">
                Request Status
            </h6>

            {{-- Pending --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:#ff4757;flex-shrink:0;"></div>
                    <span style="font-size:0.85rem;color:#4a5568;">Pending</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:100px;height:6px;border-radius:10px;background:#f0f2f7;overflow:hidden;">
                        <div style="height:100%;border-radius:10px;background:#ff4757;width:{{ $totalRequests > 0 ? round(($pendingRequests/$totalRequests)*100) : 0 }}%;"></div>
                    </div>
                    <span style="font-size:0.8rem;font-weight:600;color:#1a2035;min-width:20px;">{{ $pendingRequests }}</span>
                </div>
            </div>

            {{-- Reviewed --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></div>
                    <span style="font-size:0.85rem;color:#4a5568;">Reviewed</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:100px;height:6px;border-radius:10px;background:#f0f2f7;overflow:hidden;">
                        <div style="height:100%;border-radius:10px;background:#f59e0b;width:{{ $totalRequests > 0 ? round(($reviewedRequests/$totalRequests)*100) : 0 }}%;"></div>
                    </div>
                    <span style="font-size:0.8rem;font-weight:600;color:#1a2035;min-width:20px;">{{ $reviewedRequests }}</span>
                </div>
            </div>

            {{-- Completed --}}
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:8px;height:8px;border-radius:50%;background:#00d4aa;flex-shrink:0;"></div>
                    <span style="font-size:0.85rem;color:#4a5568;">Completed</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div style="width:100px;height:6px;border-radius:10px;background:#f0f2f7;overflow:hidden;">
                        <div style="height:100%;border-radius:10px;background:#00d4aa;width:{{ $totalRequests > 0 ? round(($completedRequests/$totalRequests)*100) : 0 }}%;"></div>
                    </div>
                    <span style="font-size:0.8rem;font-weight:600;color:#1a2035;min-width:20px;">{{ $completedRequests }}</span>
                </div>
            </div>

            <div class="mt-4 pt-3" style="border-top:1px solid #f0f2f7;">
                <a href="{{ route('service-requests.index') }}"
                   class="btn btn-sm w-100"
                   style="background:rgba(0,212,170,0.1);color:#00d4aa;border:none;font-size:0.8rem;font-weight:600;border-radius:8px;padding:8px;">
                    View All Requests
                </a>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="col-12 col-lg-4">
        <div class="dash-card p-4 h-100">
            <h6 style="font-family:'Syne',sans-serif;font-weight:700;color:#1a2035;margin-bottom:20px;">
                Quick Actions
            </h6>
            <div class="d-flex flex-column gap-2">
                <a href="{{ route('services.create') }}"
                   class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                   style="background:#f8f9fc;transition:background 0.15s;"
                   onmouseover="this.style.background='rgba(0,212,170,0.08)'"
                   onmouseout="this.style.background='#f8f9fc'">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(0,212,170,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-plus-lg" style="color:#00d4aa;font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:600;color:#1a2035;">Add Service</div>
                        <div style="font-size:0.72rem;color:#7a8499;">Create a new service listing</div>
                    </div>
                </a>

                <a href="{{ route('admin.shop-products.create') }}"
                   class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                   style="background:#f8f9fc;transition:background 0.15s;"
                   onmouseover="this.style.background='rgba(246,119,35,0.08)'"
                   onmouseout="this.style.background='#f8f9fc'">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(246,119,35,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-bag-plus" style="color:#F67723;font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:600;color:#1a2035;">Add Product</div>
                        <div style="font-size:0.72rem;color:#7a8499;">List a new shop product</div>
                    </div>
                </a>

                <a href="{{ route('backgrounds.create') }}"
                   class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                   style="background:#f8f9fc;transition:background 0.15s;"
                   onmouseover="this.style.background='rgba(99,102,241,0.08)'"
                   onmouseout="this.style.background='#f8f9fc'">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(99,102,241,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-image" style="color:#6366f1;font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:600;color:#1a2035;">Add Background</div>
                        <div style="font-size:0.72rem;color:#7a8499;">Upload a hero background</div>
                    </div>
                </a>

                <a href="{{ route('testimonials.index') }}"
                   class="d-flex align-items-center gap-3 p-3 rounded-3 text-decoration-none"
                   style="background:#f8f9fc;transition:background 0.15s;"
                   onmouseover="this.style.background='rgba(251,191,36,0.08)'"
                   onmouseout="this.style.background='#f8f9fc'">
                    <div style="width:34px;height:34px;border-radius:9px;background:rgba(251,191,36,0.12);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-chat-square-check" style="color:#f59e0b;font-size:0.9rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.85rem;font-weight:600;color:#1a2035;">
                            Review Testimonials
                            @if($pendingTestimonials > 0)
                                <span style="background:#ff4757;color:#fff;font-size:0.6rem;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ $pendingTestimonials }}</span>
                            @endif
                        </div>
                        <div style="font-size:0.72rem;color:#7a8499;">Approve or reject reviews</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    {{-- Recent Services --}}
    <div class="col-12 col-lg-4">
        <div class="dash-card p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 style="font-family:'Syne',sans-serif;font-weight:700;color:#1a2035;margin:0;">
                    Recent Services
                </h6>
                <a href="{{ route('services.index') }}" style="font-size:0.75rem;color:#00d4aa;text-decoration:none;">View all</a>
            </div>

            @forelse($recentServices as $service)
            <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:38px;height:38px;border-radius:10px;overflow:hidden;background:#f0f2f7;flex-shrink:0;">
                    @if($service->thumbnail)
                        <img src="{{ asset('public/' . $service->thumbnail) }}" alt="{{ $service->title }}"
                             style="width:100%;height:100%;object-fit:cover;">
                    @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                            <i class="bi bi-briefcase" style="color:#94a3b8;font-size:0.9rem;"></i>
                        </div>
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.82rem;font-weight:600;color:#1a2035;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        {{ $service->title }}
                    </div>
                    <div style="font-size:0.72rem;color:#7a8499;">
                        {{ $service->category->title ?? 'Uncategorized' }} · {{ number_format($service->price) }} RWF
                    </div>
                </div>
            </div>
            @empty
                <div class="text-center py-3" style="color:#94a3b8;font-size:0.85rem;">
                    <i class="bi bi-briefcase mb-2 d-block" style="font-size:1.5rem;"></i>
                    No services yet
                </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ── Recent Service Requests ── --}}
<div class="dash-card p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h6 style="font-family:'Syne',sans-serif;font-weight:700;color:#1a2035;margin:0;">
            Recent Service Requests
        </h6>
        <a href="{{ route('service-requests.index') }}"
           style="font-size:0.75rem;color:#00d4aa;text-decoration:none;">View all</a>
    </div>

    @if($recentRequests->isEmpty())
        <div class="text-center py-4" style="color:#94a3b8;">
            <i class="bi bi-inbox mb-2 d-block" style="font-size:2rem;"></i>
            <span style="font-size:0.875rem;">No requests yet</span>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead>
                    <tr style="border-bottom:2px solid #f0f2f7;">
                        <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:#7a8499;font-weight:600;padding-bottom:12px;border:none;">Client</th>
                        <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:#7a8499;font-weight:600;padding-bottom:12px;border:none;">Category</th>
                        <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:#7a8499;font-weight:600;padding-bottom:12px;border:none;" class="d-none d-md-table-cell">Budget</th>
                        <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:#7a8499;font-weight:600;padding-bottom:12px;border:none;">Status</th>
                        <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.06em;color:#7a8499;font-weight:600;padding-bottom:12px;border:none;" class="d-none d-md-table-cell">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentRequests as $req)
                    <tr style="border-bottom:1px solid #f8f9fc;">
                        <td style="padding:12px 0;border:none;">
                            <div style="font-weight:600;color:#1a2035;">{{ $req->name }}</div>
                            <div style="font-size:0.72rem;color:#7a8499;">{{ $req->phone }}</div>
                        </td>
                        <td style="border:none;">
                            @if($req->service_category)
                                <span style="background:rgba(0,212,170,0.1);color:#00d4aa;font-size:0.7rem;font-weight:600;padding:3px 9px;border-radius:20px;">
                                    {{ $req->service_category }}
                                </span>
                            @else
                                <span style="color:#94a3b8;font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td style="border:none;" class="d-none d-md-table-cell">
                            <span style="color:#4a5568;font-size:0.8rem;">{{ $req->budget ?: '—' }}</span>
                        </td>
                        <td style="border:none;">
                            @php
                                $statusColor = match($req->status) {
                                    'pending'   => ['bg' => 'rgba(255,71,87,0.1)',  'text' => '#ff4757'],
                                    'reviewed'  => ['bg' => 'rgba(251,191,36,0.1)', 'text' => '#f59e0b'],
                                    'completed' => ['bg' => 'rgba(0,212,170,0.1)',  'text' => '#00d4aa'],
                                    default     => ['bg' => '#f0f2f7',              'text' => '#7a8499'],
                                };
                            @endphp
                            <span style="background:{{ $statusColor['bg'] }};color:{{ $statusColor['text'] }};font-size:0.7rem;font-weight:600;padding:3px 9px;border-radius:20px;text-transform:capitalize;">
                                {{ $req->status }}
                            </span>
                        </td>
                        <td style="border:none;color:#7a8499;font-size:0.78rem;" class="d-none d-md-table-cell">
                            {{ $req->created_at->format('M d, Y') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection