@extends('front.layouts.admin')
@section('page-title', 'Vendors')

@push('styles')
<style>
    .vendor-page { max-width:1440px; margin:0 auto; }
    .vendor-page-header { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:20px; }
    .vendor-page-title { font-family:Inter,ui-sans-serif,system-ui,sans-serif; font-size:26px; line-height:1.2; font-weight:700; color:#0f172a; margin:0 0 4px; }
    .vendor-page-description { color:#64748b; font-size:14px; margin:0; }
    .vendor-register-btn { height:42px; display:inline-flex; align-items:center; gap:7px; border-radius:8px; background:#2563eb; border-color:#2563eb; font-size:14px; font-weight:600; white-space:nowrap; }
    .vendor-register-btn:hover { background:#1d4ed8; border-color:#1d4ed8; }
    .vendor-summary-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:16px; }
    .vendor-summary-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; }
    .vendor-summary-label { color:#64748b; font-size:12px; font-weight:600; margin-bottom:5px; }
    .vendor-summary-value { color:#0f172a; font-size:22px; line-height:1; font-weight:700; }
    .vendor-tabs { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; border-bottom:1px solid #e2e8f0; }
    .vendor-tab { padding:10px 14px; border-bottom:2px solid transparent; color:#475569; font-size:14px; font-weight:600; text-decoration:none; }
    .vendor-tab:hover,.vendor-tab.active { border-bottom-color:#2563eb; color:#2563eb; }
    .vendor-tab:focus-visible { outline:3px solid #2563eb; outline-offset:3px; }
    .vendor-toolbar { background:#fff; border:1px solid #e2e8f0; border-radius:10px 10px 0 0; padding:12px 16px; }
    .vendor-filter-row { display:flex; align-items:center; gap:8px; }
    .vendor-search { flex:1 1 auto; min-width:180px; }
    .vendor-status-filter { width:190px; flex:0 0 190px; }
    .vendor-filter-btn { height:40px; flex:0 0 auto; border-radius:8px; font-size:14px; font-weight:600; }
    .vendor-table-card { background:#fff; border:1px solid #e2e8f0; border-top:0; border-radius:0 0 10px 10px; overflow:hidden; }
    .vendor-table { width:100%; margin:0; table-layout:fixed; font-size:13px; }
    .vendor-table thead th { height:42px; padding:8px 12px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:12px; font-weight:600; vertical-align:middle; }
    .vendor-table tbody td { height:64px; padding:10px 12px; border-bottom:1px solid #edf2f7; color:#334155; vertical-align:middle; }
    .vendor-table tbody tr:last-child td { border-bottom:0; }
    .vendor-table tbody tr:hover { background:#f8fafc; }
    .vendor-col-name{width:24%}.vendor-col-address{width:25%}.vendor-col-status{width:14%}.vendor-col-products{width:12%}.vendor-col-joined{width:13%}.vendor-col-actions{width:12%}
    .vendor-identity { display:flex; align-items:center; gap:10px; min-width:0; }
    .vendor-avatar { position:relative; width:40px; height:40px; flex:0 0 40px; border-radius:50%; overflow:hidden; background:#e2e8f0; color:#475569; display:inline-flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; }
    .vendor-avatar img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .vendor-name { min-width:0; color:#0f172a; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .vendor-address { color:#475569; line-height:1.35; }
    .vendor-status-badge { display:inline-flex; align-items:center; border-radius:999px; padding:4px 9px; font-size:12px; font-weight:600; white-space:nowrap; }
    .vendor-status-active { color:#15803d; background:#dcfce7; border:1px solid #bbf7d0; }
    .vendor-status-pending { color:#b45309; background:#fef3c7; border:1px solid #fde68a; }
    .vendor-status-suspended { color:#b91c1c; background:#fee2e2; border:1px solid #fecaca; }
    .vendor-status-inactive { color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; }
    .vendor-status-unknown { color:#475569; background:#f8fafc; border:1px solid #e2e8f0; }
    .vendor-actions { display:flex; justify-content:center; gap:4px; white-space:nowrap; }
    .vendor-action { color:#2563eb; text-decoration:none; font-size:13px; font-weight:600; padding:4px 5px; border-radius:6px; }
    .vendor-action:hover { color:#1d4ed8; background:#eff6ff; }
    .vendor-action-edit { color:#475569; }
    .vendor-empty { text-align:center; color:#64748b; height:120px !important; }
    .vendor-pagination { padding:12px 16px; border-top:1px solid #e2e8f0; }
    .application-table-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; }
    .application-table-wrap { overflow-x:auto; }
    .application-table { width:100%; min-width:1200px; table-layout:fixed; margin:0; font-size:13px; }
    .application-table thead th { height:44px; padding:9px 10px; background:#f8fafc; border-bottom:1px solid #e2e8f0; color:#475569; font-size:12px; font-weight:600; vertical-align:middle; }
    .application-table tbody td { height:64px; padding:10px; border-bottom:1px solid #edf2f7; color:#334155; vertical-align:middle; }
    .application-table tbody tr:last-child td { border-bottom:0; }
    .application-table tbody tr:hover { background:#f8fafc; }
    .application-col-logo { width:6%; text-align:center; }
    .application-col-business { width:14%; }
    .application-col-contact { width:10%; }
    .application-col-tin { width:7%; text-align:center; }
    .application-col-phone { width:10%; }
    .application-col-email { width:15%; }
    .application-col-date { width:11%; text-align:center; }
    .application-col-status { width:12%; text-align:center; }
    .application-col-action { width:15%; text-align:center; }
    .application-avatar { position:relative; display:inline-flex; width:40px; height:40px; align-items:center; justify-content:center; overflow:hidden; border-radius:9px; background:#e2e8f0; color:#475569; font-size:14px; font-weight:700; }
    .application-avatar img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .application-value { display:block; min-width:0; overflow-wrap:anywhere; }
    .application-status-badge { display:inline-flex; align-items:center; justify-content:center; border:1px solid transparent; border-radius:999px; padding:4px 9px; font-size:12px; font-weight:600; line-height:1.2; white-space:nowrap; }
    .application-status-pending { color:#b45309; background:#fef3c7; border-color:#fde68a; }
    .application-status-correction { color:#1d4ed8; background:#eff6ff; border-color:#bfdbfe; }
    .review-application-btn { display:inline-flex; min-height:36px; align-items:center; justify-content:center; gap:6px; border:1px solid #2563eb; border-radius:7px; padding:7px 10px; background:#2563eb; color:#fff; font-size:12px; font-weight:600; text-decoration:none; white-space:nowrap; }
    .review-application-btn:hover { border-color:#1d4ed8; background:#1d4ed8; color:#fff; }
    .review-application-btn:focus-visible { outline:3px solid #93c5fd; outline-offset:2px; }
    .application-empty { padding:44px 20px !important; color:#64748b !important; text-align:center; }
    .application-empty i { display:block; margin-bottom:8px; color:#94a3b8; font-size:24px; }
    .application-pagination { padding:12px 16px; border-top:1px solid #e2e8f0; }
    @media(max-width:991.98px){.vendor-page-header{align-items:flex-start}.vendor-table-card{overflow-x:auto}.vendor-table{min-width:760px}}
    @media(max-width:575.98px){.vendor-page-header{flex-direction:column}.vendor-register-btn{width:100%;justify-content:center}.vendor-summary-grid{grid-template-columns:1fr}.vendor-filter-row{flex-direction:column;align-items:stretch}.vendor-status-filter{width:100%;flex-basis:auto}.vendor-filter-btn{width:100%}}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 vendor-page">
    @include('marketplace.partials.flash')

    <div class="vendor-page-header">
        <div><h1 class="vendor-page-title">Vendors</h1><p class="vendor-page-description">Manage and monitor registered marketplace vendors.</p></div>
        @if(auth()->user()->hasPermission('vendors.create'))
            <a class="btn btn-primary vendor-register-btn" href="{{ route('marketplace.vendors.create') }}"><i class="bi bi-plus-lg" aria-hidden="true"></i>Register Vendor</a>
        @endif
    </div>

    <div class="vendor-summary-grid" aria-label="Vendor summary">
        <div class="vendor-summary-card"><div class="vendor-summary-label">Total Vendors</div><div class="vendor-summary-value">{{ number_format($summary['total']) }}</div></div>
        <div class="vendor-summary-card"><div class="vendor-summary-label">Active Vendors</div><div class="vendor-summary-value">{{ number_format($summary['active']) }}</div></div>
        <div class="vendor-summary-card"><div class="vendor-summary-label">Terminated Vendors</div><div class="vendor-summary-value">{{ number_format($summary['terminated']) }}</div></div>
    </div>

    @php
        $tabQuery = request()->except('page');
        $verifiedTabUrl = route('marketplace.vendors.index', array_merge($tabQuery, ['tab' => 'verified']));
        $unverifiedTabUrl = route('marketplace.vendors.index', array_merge($tabQuery, ['tab' => 'unverified']));
    @endphp
    <nav class="vendor-tabs" aria-label="Vendor categories">
        <a class="vendor-tab {{ $activeTab === 'verified' ? 'active' : '' }}"
           href="{{ $verifiedTabUrl }}"
           @if($activeTab === 'verified') aria-current="page" @endif>
            Verified Vendors
        </a>
        <a class="vendor-tab {{ $activeTab === 'unverified' ? 'active' : '' }}"
           href="{{ $unverifiedTabUrl }}"
           @if($activeTab === 'unverified') aria-current="page" @endif>
            Unverified Applications
        </a>
    </nav>

    @if($activeTab === 'verified')
    <form method="GET" action="{{ route('marketplace.vendors.index') }}" class="vendor-toolbar">
        <input type="hidden" name="tab" value="verified">
        <div class="vendor-filter-row">
            <label class="visually-hidden" for="vendor-search">Search vendors</label>
            <input id="vendor-search" name="q" value="{{ request('q') }}" class="form-control vendor-search" placeholder="Search vendors" autocomplete="off">
            <label class="visually-hidden" for="vendor-status">Status</label>
            <select id="vendor-status" name="status" class="form-select vendor-status-filter">
                <option value="">All statuses</option>
                @foreach(['pending','active','suspended','inactive'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary vendor-filter-btn"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter</button>
        </div>
    </form>

    <div class="vendor-table-card">
        <table class="table vendor-table">
            <thead><tr><th class="vendor-col-name text-start">Vendor</th><th class="vendor-col-address text-start">Address</th><th class="vendor-col-status text-center">Status</th><th class="vendor-col-products text-center">Total Products</th><th class="vendor-col-joined text-center">Joined</th><th class="vendor-col-actions text-center">Actions</th></tr></thead>
            <tbody>
                @forelse($vendors as $vendor)
                    @php
                        $logoPath = ltrim((string) $vendor->business_logo_path, '/');
                        $logoExists = $logoPath !== '' && ! str_contains($logoPath, '..') && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
                        $initial = mb_strtoupper(mb_substr(trim((string) $vendor->business_name), 0, 1));
                        $addressParts = collect([$vendor->operating_location, $vendor->address_notes, $vendor->district])->filter(fn ($value) => is_string($value) && trim($value) !== '')->map(fn ($value) => trim($value))->unique()->values();
                        $statusClass = match ($vendor->status) {
                            'active' => 'vendor-status-active', 'pending' => 'vendor-status-pending', 'suspended' => 'vendor-status-suspended', 'inactive' => 'vendor-status-inactive', default => 'vendor-status-unknown',
                        };
                    @endphp
                    <tr>
                        <td><div class="vendor-identity"><span class="vendor-avatar" aria-hidden="true"><span>{{ $initial !== '' ? $initial : 'V' }}</span>@if($logoExists)<img src="{{ asset('assets/'.$logoPath) }}" alt="{{ $vendor->business_name }} logo" onerror="this.style.display='none'">@endif</span><span class="vendor-name" title="{{ $vendor->business_name }}">{{ $vendor->business_name }}</span></div></td>
                        <td><span class="vendor-address">{{ $addressParts->isNotEmpty() ? $addressParts->implode(', ') : '—' }}</span></td>
                        <td class="text-center"><span class="vendor-status-badge {{ $statusClass }}">{{ $vendor->statusLabel() }}</span></td>
                        <td class="text-center">{{ number_format($vendor->products_count) }}</td>
                        <td class="text-center">{{ $vendor->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td><div class="vendor-actions"><a class="vendor-action" href="{{ route('marketplace.vendors.show', $vendor) }}">View</a>@if(auth()->user()->hasPermission('vendors.manage'))<span class="text-muted" aria-hidden="true">·</span><a class="vendor-action vendor-action-edit" href="{{ route('marketplace.vendors.edit', $vendor) }}">Edit</a>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="vendor-empty">No vendors found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($vendors->hasPages())<div class="vendor-pagination">{{ $vendors->links() }}</div>@endif
    </div>
    @else
        <form method="GET" action="{{ route('marketplace.vendors.index') }}" class="vendor-toolbar mb-3">
            <input type="hidden" name="tab" value="unverified">
            <div class="vendor-filter-row">
                <label class="visually-hidden" for="application-search">Search applications</label>
                <input id="application-search" name="q" value="{{ request('q') }}" class="form-control vendor-search" placeholder="Search business, contact, TIN, phone, or email" autocomplete="off">
                <button type="submit" class="btn btn-primary vendor-filter-btn"><i class="bi bi-search me-1" aria-hidden="true"></i>Search</button>
            </div>
        </form>

        <div class="application-table-card">
            <div class="application-table-wrap">
                <table class="table application-table">
                    <thead>
                        <tr>
                            <th scope="col" class="application-col-logo">Business Logo</th>
                            <th scope="col" class="application-col-business">Business Name</th>
                            <th scope="col" class="application-col-contact">Contact Person</th>
                            <th scope="col" class="application-col-tin">TIN</th>
                            <th scope="col" class="application-col-phone">Business Phone</th>
                            <th scope="col" class="application-col-email">Business Email</th>
                            <th scope="col" class="application-col-date">Submitted Date</th>
                            <th scope="col" class="application-col-status">Application Status</th>
                            <th scope="col" class="application-col-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $application)
                            @php
                                $logoPath = ltrim((string) $application->business_logo_path, '/');
                                $logoExists = $logoPath !== '' && ! str_contains($logoPath, '..') && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
                                $initial = mb_strtoupper(mb_substr(trim((string) $application->business_name), 0, 1));
                                $applicationStatusClass = $application->application_status === 'correction_requested'
                                    ? 'application-status-correction'
                                    : 'application-status-pending';
                            @endphp
                            <tr>
                                <td class="application-col-logo">
                                    <span class="application-avatar" aria-hidden="true">
                                        <span>{{ $initial !== '' ? $initial : 'V' }}</span>
                                        @if($logoExists)<img src="{{ asset('assets/'.$logoPath) }}" alt="" loading="lazy" onerror="this.style.display='none'">@endif
                                    </span>
                                </td>
                                <td class="application-col-business"><span class="application-value fw-semibold">{{ $application->business_name }}</span></td>
                                <td class="application-col-contact"><span class="application-value">{{ $application->contact_person ?: '—' }}</span></td>
                                <td class="application-col-tin"><span class="application-value">{{ $application->tin ?: '—' }}</span></td>
                                <td class="application-col-phone"><span class="application-value">{{ $application->phone ?: '—' }}</span></td>
                                <td class="application-col-email"><span class="application-value">{{ $application->business_email ?: $application->email ?: '—' }}</span></td>
                                <td class="application-col-date">{{ $application->application_submitted_at?->format('M j, Y H:i') ?? '—' }}</td>
                                <td class="application-col-status"><span class="application-status-badge {{ $applicationStatusClass }}">{{ $application->applicationStatusLabel() }}</span></td>
                                <td class="application-col-action">
                                    @if(auth()->user()->hasPermission('vendors.manage'))
                                        <a class="review-application-btn" href="{{ route('marketplace.vendor-applications.show', $application) }}">
                                            <i class="bi bi-eye" aria-hidden="true"></i>Review Application
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="application-empty" colspan="9"><i class="bi bi-inbox" aria-hidden="true"></i>No vendor applications awaiting review.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())
                <div class="application-pagination">{{ $applications->links() }}</div>
            @endif
        </div>
    @endif
</div>
@endsection