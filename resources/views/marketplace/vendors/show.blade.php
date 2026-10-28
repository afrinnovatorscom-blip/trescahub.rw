@extends('front.layouts.admin')
@section('page-title', $vendor->business_name)
@push('styles')
<style>
    .vendor-details-page { max-width: 1500px; color: #0f172a; }
    .vendor-header { display:flex; align-items:center; justify-content:space-between; gap:20px; padding:24px; color:#fff; background:#0f172a; border:1px solid #263248; border-radius:14px; }
    .vendor-identity { display:flex; align-items:center; gap:18px; min-width:0; }
    .vendor-logo { display:flex; width:72px; height:72px; flex:0 0 72px; align-items:center; justify-content:center; overflow:hidden; border:2px solid rgba(255,255,255,.35); border-radius:50%; background:#2563eb; color:#fff; font-size:1.35rem; font-weight:700; }
    .vendor-logo img { width:100%; height:100%; border-radius:50%; object-fit:cover; }
    .vendor-title { min-width:0; }
    .vendor-title h1 { margin:0 0 5px; font-size:clamp(1.25rem,2.5vw,1.8rem); font-weight:700; overflow-wrap:anywhere; }
    .vendor-reference { color:#cbd5e1; font-size:.9rem; }
    .vendor-badges { display:flex; flex-wrap:wrap; gap:7px; margin-top:10px; }
    .vendor-status { padding:4px 9px; border:1px solid rgba(255,255,255,.2); border-radius:999px; background:rgba(255,255,255,.08); font-size:.75rem; }
    .vendor-status-active { color:#bbf7d0; border-color:rgba(34,197,94,.35); }
    .vendor-status-review { color:#fde68a; border-color:rgba(251,191,36,.4); }
    .vendor-status-suspended { color:#fecaca; border-color:rgba(248,113,113,.4); }
    .vendor-status-unknown { color:#e2e8f0; border-color:rgba(226,232,240,.35); border-style:dashed; }
    .vendor-menu .dropdown-menu { min-width:220px; }
    .vendor-metrics { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin:20px 0; }
    .vendor-metric { display:flex; align-items:center; gap:14px; min-height:112px; padding:18px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; color:#0f172a; text-decoration:none; box-shadow:0 2px 7px rgba(15,23,42,.04); transition:transform .16s ease,box-shadow .16s ease,border-color .16s ease; }
    .vendor-metric:hover { transform:translateY(-2px); border-color:#bfdbfe; box-shadow:0 7px 18px rgba(15,23,42,.09); color:#0f172a; }
    .vendor-metric:focus-visible,.vendor-tab:focus-visible { outline:3px solid #2563eb; outline-offset:3px; }
    .vendor-metric-icon { display:flex; width:44px; height:44px; flex:0 0 44px; align-items:center; justify-content:center; border-radius:11px; background:#eff6ff; color:#2563eb; font-size:1.2rem; }
    .vendor-metric-label { color:#64748b; font-size:.84rem; }
    .vendor-metric-value { margin-top:3px; font-size:1.45rem; font-weight:700; line-height:1.2; overflow-wrap:anywhere; }
    .vendor-nav { display:flex; gap:6px; overflow-x:auto; padding:0 0 8px; border-bottom:1px solid #e2e8f0; }
    .vendor-tab { flex:0 0 auto; padding:10px 14px; border-bottom:2px solid transparent; color:#475569; font-size:.9rem; font-weight:600; text-decoration:none; }
    .vendor-tab:hover,.vendor-tab.active { border-bottom-color:#2563eb; color:#2563eb; }
    .vendor-panel { margin-top:18px; padding:22px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; box-shadow:0 2px 7px rgba(15,23,42,.035); scroll-margin-top:90px; }
    .vendor-panel h2 { margin-bottom:16px; font-size:1.08rem; font-weight:700; }
    .vendor-info-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px 24px; }
    .vendor-info-label { display:block; margin-bottom:3px; color:#64748b; font-size:.78rem; }
    .vendor-info-value { overflow-wrap:anywhere; font-size:.93rem; }
    .vendor-data-table th { color:#64748b; font-size:.78rem; font-weight:600; white-space:nowrap; }
    .vendor-data-table td { vertical-align:middle; }
    .vendor-empty { padding:22px 12px; color:#64748b; text-align:center; }
    @media(max-width:991.98px) { .vendor-metrics { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(max-width:575.98px) { .vendor-header { align-items:flex-start; padding:18px; } .vendor-identity { align-items:flex-start; gap:12px; } .vendor-logo { width:58px; height:58px; flex-basis:58px; } .vendor-metrics { gap:10px; } .vendor-metric { min-height:100px; gap:10px; padding:13px; } .vendor-metric-icon { width:36px; height:36px; flex-basis:36px; font-size:1rem; } .vendor-metric-value { font-size:1.15rem; } .vendor-info-grid { grid-template-columns:1fr; } .vendor-panel { padding:16px; } }
</style>
@endpush
@section('content')
@php
    $logoPath = ltrim((string) $vendor->business_logo_path, '/');
    $logoExists = $logoPath !== ''
        && ! str_contains($logoPath, '..')
        && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
    $initialSource = trim((string) ($vendor->business_name ?: ''));
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($initialSource ?: 'A', 0, 1));
    $canViewContacts = auth()->user()->hasPermission('vendors.view_contacts');
    $canViewLocations = auth()->user()->hasPermission('locations.view');
    $canViewFinancials = auth()->user()->hasPermission('finance.view');
@endphp
<div class="container-fluid py-4 vendor-details-page">
    @include('marketplace.partials.flash')

    <header class="vendor-header">
        <div class="vendor-identity">
            <div class="vendor-logo" aria-label="{{ $vendor->business_name }} logo">
                @if($logoExists)
                    <img src="{{ asset('assets/'.$logoPath) }}" alt="{{ $vendor->business_name }} logo" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                @endif
                <span @if($logoExists) hidden @endif>{{ $initial }}</span>
            </div>
            <div class="vendor-title">
                <h1>{{ $vendor->business_name }}</h1>
                <div class="vendor-reference">Reference: {{ $vendor->reference_number ?: '—' }}</div>
                <div class="vendor-badges">
                    <span class="vendor-status {{ $vendor->statusBadgeClass() }}">
                        Vendor: {{ $vendor->statusLabel() }}
                    </span>
                    <span class="vendor-status">
                        Partner: {{ $vendor->partnerStatusLabel() }}
                    </span>
                    <span class="vendor-status {{ $vendor->public_branding_approved === true ? 'vendor-status-active' : ($vendor->public_branding_approved === false ? 'vendor-status-review' : 'vendor-status-unknown') }}">
                        Branding: {{ $vendor->brandingStatusLabel() }}
                    </span>
                    <span class="vendor-status">
                        Website: {{ $vendor->websitePublicationLabel() }}
                    </span>
                </div>
            </div>
        </div>

        @if(auth()->user()->hasPermission('vendors.manage'))
            <div class="dropdown vendor-menu">
                <button class="btn btn-outline-light" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Vendor actions">
                    <i class="bi bi-three-dots-vertical" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('marketplace.vendors.edit', $vendor) }}"><i class="bi bi-pencil me-2" aria-hidden="true"></i>Edit Vendor</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button class="dropdown-item {{ $vendor->hasWebsitePublicationEnabled() ? 'text-danger' : '' }}"
                                type="button" data-bs-toggle="modal" data-bs-target="#vendorPublicationModal">
                            <i class="bi {{ $vendor->hasWebsitePublicationEnabled() ? 'bi-eye-slash' : 'bi-globe2' }} me-2" aria-hidden="true"></i>
                            {{ $vendor->hasWebsitePublicationEnabled() ? 'Remove from Website' : 'Publish on Website' }}
                        </button>
                    </li>
                    @if($vendor->status !== 'suspended')
                        <li><hr class="dropdown-divider"></li>
                        <li><button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#terminateVendorModal"><i class="bi bi-slash-circle me-2" aria-hidden="true"></i>Terminate Vendor</button></li>
                    @endif
                </ul>
            </div>
        @endif
    </header>

    <section class="vendor-metrics" aria-label="Vendor summary">
        <a class="vendor-metric" href="{{ route('admin.shop-products.index', ['vendor_id' => $vendor->id]) }}" aria-label="View {{ $vendor->products_count }} products for {{ $vendor->business_name }}">
            <span class="vendor-metric-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
            <span><span class="vendor-metric-label">Products</span><span class="vendor-metric-value d-block">{{ number_format($vendor->products_count) }}</span></span>
        </a>
        <a class="vendor-metric" href="{{ route('marketplace.orders.index', ['vendor' => $vendor->id]) }}" aria-label="View {{ $orderCount }} orders for {{ $vendor->business_name }}">
            <span class="vendor-metric-icon"><i class="bi bi-bag-check" aria-hidden="true"></i></span>
            <span><span class="vendor-metric-label">Orders</span><span class="vendor-metric-value d-block">{{ number_format($orderCount) }}</span></span>
        </a>
        <a class="vendor-metric" href="{{ route('marketplace.orders.index', ['vendor' => $vendor->id, 'payment' => 'paid']) }}" aria-label="View paid orders for {{ $vendor->business_name }}">
            <span class="vendor-metric-icon"><i class="bi bi-currency-exchange" aria-hidden="true"></i></span>
            <span><span class="vendor-metric-label">Sales Total</span><span class="vendor-metric-value d-block">{{ $canViewFinancials ? number_format((float) $salesTotal, 0, '.', ',').' RWF' : 'Restricted' }}</span></span>
        </a>
        <a class="vendor-metric" href="{{ route('marketplace.inventory.index', ['vendor_id' => $vendor->id]) }}" aria-label="View inventory for {{ $vendor->business_name }}">
            <span class="vendor-metric-icon"><i class="bi bi-boxes" aria-hidden="true"></i></span>
            <span><span class="vendor-metric-label">Available Stock</span><span class="vendor-metric-value d-block">{{ number_format($availableStock) }}</span></span>
        </a>
    </section>

    <nav class="vendor-nav" aria-label="Vendor details sections">
        <a class="vendor-tab active" href="#overview">Overview</a>
        <a class="vendor-tab" href="#business-information">Business Information</a>
        <a class="vendor-tab" href="#products">Products</a>
        <a class="vendor-tab" href="#orders">Orders</a>
        @if($activity->isNotEmpty())<a class="vendor-tab" href="#activity">Activity</a>@endif
    </nav>

    <section class="vendor-panel" id="overview" aria-labelledby="overview-heading">
        <h2 id="overview-heading">Overview</h2>
        <div class="vendor-info-grid">
            <div><span class="vendor-info-label">Operational Status</span><span class="vendor-info-value">{{ $vendor->statusLabel() }}</span></div>
            <div><span class="vendor-info-label">Registered</span><span class="vendor-info-value">{{ $vendor->created_at?->format('M j, Y') ?? '—' }}</span></div>
            <div><span class="vendor-info-label">Partner Display</span><span class="vendor-info-value">{{ $vendor->partnerStatusLabel() }}</span></div>
            <div><span class="vendor-info-label">Public Branding Approval</span><span class="vendor-info-value">{{ $vendor->brandingStatusLabel() }}</span></div>
        </div>
    </section>

    <section class="vendor-panel" id="business-information" aria-labelledby="business-information-heading">
        <h2 id="business-information-heading">Business Information</h2>
        <div class="vendor-info-grid">
            <div><span class="vendor-info-label">Legal Name</span><span class="vendor-info-value">{{ $vendor->legal_name ?: '—' }}</span></div>
            <div><span class="vendor-info-label">Reference</span><span class="vendor-info-value">{{ $vendor->reference_number ?: '—' }}</span></div>
            @if($canViewContacts)
                <div><span class="vendor-info-label">Email</span><span class="vendor-info-value">{{ $vendor->email ?: '—' }}</span></div>
                <div><span class="vendor-info-label">Phone</span><span class="vendor-info-value">{{ $vendor->phone ?: '—' }}</span></div>
            @else
                <div class="text-muted">Contact details are hidden for your role.</div>
            @endif
            @if($canViewLocations)
                <div><span class="vendor-info-label">District</span><span class="vendor-info-value">{{ $vendor->district ?: '—' }}</span></div>
                <div><span class="vendor-info-label">Operating Location</span><span class="vendor-info-value">{{ $vendor->operating_location ?: '—' }}</span></div>
                <div><span class="vendor-info-label">Address Notes</span><span class="vendor-info-value">{{ $vendor->address_notes ?: '—' }}</span></div>
            @endif
            <div>
                <span class="vendor-info-label">Website</span>
                <span class="vendor-info-value">
                    @if($vendor->website_url)<a href="{{ $vendor->website_url }}" target="_blank" rel="noopener noreferrer">{{ $vendor->website_url }}</a>@else—@endif
                </span>
            </div>
            @if($vendor->partner_description)
                <div><span class="vendor-info-label">Partner Description</span><span class="vendor-info-value">{{ $vendor->partner_description }}</span></div>
            @endif
        </div>
    </section>

    <section class="vendor-panel" id="products" aria-labelledby="products-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="mb-0" id="products-heading">Products</h2>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.shop-products.index', ['vendor_id' => $vendor->id]) }}">Manage Products</a>
        </div>
        <div class="table-responsive">
            <table class="table vendor-data-table mb-0">
                <thead><tr><th>Product</th><th>Category</th><th>Approval</th><th>Availability</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td><a href="{{ route('admin.shop-products.show', $product) }}">{{ $product->product_name }}</a></td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ str_replace('_', ' ', $product->approval_status ?? 'pending') }}</td>
                            <td>{{ $product->tracks_inventory ? number_format($product->availableQuantity()).' '.($product->stock_unit ?: 'units') : 'Not stock-managed' }}</td>
                        </tr>
                    @empty
                        <tr><td class="vendor-empty" colspan="4">No products are assigned to this vendor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $products->fragment('products')->links() }}</div>
    </section>

    <section class="vendor-panel" id="orders" aria-labelledby="orders-heading">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="mb-0" id="orders-heading">Orders</h2>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('marketplace.orders.index', ['vendor' => $vendor->id]) }}">Manage Orders</a>
        </div>
        <div class="table-responsive">
            <table class="table vendor-data-table mb-0">
                <thead><tr><th>Reference</th><th>Items</th><th>Payment</th><th>Fulfillment</th><th>Vendor Subtotal</th></tr></thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><a href="{{ route('marketplace.orders.show', $order) }}">{{ $order->reference }}</a></td>
                            <td>{{ $order->items->count() }}</td>
                            <td>{{ ucfirst($order->payment_status) }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($order->fulfillment_status)) }}</td>
                            <td>{{ number_format((float) $order->items->sum('line_total'), 0, '.', ',') }} RWF</td>
                        </tr>
                    @empty
                        <tr><td class="vendor-empty" colspan="5">No orders are associated with this vendor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $orders->fragment('orders')->links() }}</div>
    </section>

    @if($activity->isNotEmpty())
        <section class="vendor-panel" id="activity" aria-labelledby="activity-heading">
            <h2 id="activity-heading">Activity</h2>
            <div class="table-responsive">
                <table class="table vendor-data-table mb-0">
                    <thead><tr><th>Action</th><th>Administrator</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($activity as $entry)
                            <tr><td>{{ str($entry->action)->headline() }}</td><td>{{ $entry->actor?->name ?? 'System' }}</td><td>{{ $entry->created_at?->format('M j, Y H:i') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<script>
    (() => {
        const tabs = document.querySelectorAll('.vendor-nav .vendor-tab');
        const activateTab = (hash) => {
            tabs.forEach((tab) => {
                const active = tab.getAttribute('href') === hash;
                tab.classList.toggle('active', active);
                if (active) tab.setAttribute('aria-current', 'location');
                else tab.removeAttribute('aria-current');
            });
        };

        tabs.forEach((tab) => tab.addEventListener('click', () => activateTab(tab.getAttribute('href'))));
        if (window.location.hash) activateTab(window.location.hash);
    })();
</script>

@if(auth()->user()->hasPermission('vendors.manage') && $vendor->status !== 'suspended')
    <div class="modal fade" id="terminateVendorModal" tabindex="-1" aria-labelledby="terminateVendorTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="terminateVendorTitle">Terminate Vendor</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>This action suspends <strong>{{ $vendor->business_name }}</strong>'s operational access. Vendor users will no longer be able to use the vendor workspace, and their products will not be publicly available while the vendor is suspended.</p>
                    <p class="mb-0">This does not delete products, orders, inventory, accounts, or history. An authorized administrator can restore access by changing the vendor status.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('marketplace.vendors.terminate', $vendor) }}">
                        @csrf
                        <input type="hidden" name="confirmed" value="1">
                        <button type="submit" class="btn btn-danger">Confirm Termination</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@if(auth()->user()->hasPermission('vendors.manage'))
    <div class="modal fade" id="vendorPublicationModal" tabindex="-1" aria-labelledby="vendorPublicationTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="vendorPublicationTitle">
                        {{ $vendor->hasWebsitePublicationEnabled() ? 'Remove from Website' : 'Publish on Website' }}
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($vendor->hasWebsitePublicationEnabled())
                        <p class="mb-0">
                            Remove <strong>{{ $vendor->business_name }}</strong> from the homepage's Our Partners section?
                            The vendor and its related business records will remain unchanged.
                        </p>
                    @else
                        <p class="mb-0">
                            Publish <strong>{{ $vendor->business_name }}</strong> on the homepage's Our Partners section?
                            Its business name and logo will be publicly visible. The vendor must be active, marked as a
                            partner, and have approved public branding.
                        </p>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="{{ route('marketplace.vendors.publication', $vendor) }}"
                          onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="{{ $vendor->hasWebsitePublicationEnabled() ? 'unpublish' : 'publish' }}">
                        <input type="hidden" name="confirmed" value="1">
                        <button type="submit" class="btn {{ $vendor->hasWebsitePublicationEnabled() ? 'btn-danger' : 'btn-primary' }}">
                            {{ $vendor->hasWebsitePublicationEnabled() ? 'Confirm Removal' : 'Confirm Publication' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection
