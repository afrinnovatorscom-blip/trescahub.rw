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

    <form method="GET" action="{{ route('marketplace.vendors.index') }}" class="vendor-toolbar">
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
</div>
@endsection