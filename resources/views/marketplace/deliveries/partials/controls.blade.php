@once
    @push('styles')
        <style>
            .delivery-page { color: #0f172a; }
            .delivery-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
            .delivery-tab {
                align-items: center;
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                color: #475569;
                display: inline-flex;
                font-size: .875rem;
                font-weight: 500;
                gap: 8px;
                padding: 8px 12px;
                text-decoration: none;
            }
            .delivery-tab:hover { background: #f8fafc; color: #0f172a; }
            .delivery-tab.active {
                background: #eff6ff;
                border-color: #bfdbfe;
                color: #1d4ed8;
            }
            .delivery-tab-count {
                background: #f1f5f9;
                border-radius: 999px;
                color: #475569;
                font-size: .72rem;
                line-height: 1.4;
                min-width: 22px;
                padding: 1px 6px;
                text-align: center;
            }
            .delivery-tab.active .delivery-tab-count { background: #dbeafe; color: #1e40af; }
            .delivery-filter-grid {
                align-items: end;
                display: grid;
                gap: 8px;
                grid-template-columns: minmax(0, 1.5fr) minmax(145px, .75fr) minmax(145px, .75fr) auto;
            }
            .delivery-filter-grid.has-vendor {
                grid-template-columns: minmax(0, 1.4fr) minmax(145px, .7fr) minmax(145px, .7fr) minmax(145px, .7fr) auto;
            }
            .delivery-filter-grid .form-control,
            .delivery-filter-grid .form-select,
            .delivery-filter-actions .btn { min-height: 40px; }
            .delivery-filter-actions { align-items: center; display: flex; gap: 10px; white-space: nowrap; }
            .delivery-empty { color: #64748b; padding: 38px 16px; text-align: center; }
            .delivery-table thead th {
                background: #f8fafc;
                border-bottom-color: #e2e8f0;
                color: #64748b;
                font-size: .72rem;
                font-weight: 600;
                padding: 12px 14px;
                white-space: nowrap;
            }
            .delivery-table tbody td {
                border-color: #f1f5f9;
                color: #334155;
                font-size: .84rem;
                padding: 13px 14px;
                vertical-align: middle;
            }
            .delivery-status {
                border: 1px solid transparent;
                border-radius: 999px;
                display: inline-flex;
                font-size: .72rem;
                font-weight: 600;
                padding: 4px 9px;
                white-space: nowrap;
            }
            .delivery-status-assigned { background: #fffbeb; border-color: #fde68a; color: #92400e; }
            .delivery-status-in-transit { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }
            .delivery-status-delivered { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
            @media (max-width: 991.98px) {
                .delivery-filter-grid,
                .delivery-filter-grid.has-vendor { grid-template-columns: minmax(0, 1fr) minmax(140px, .6fr); }
                .delivery-filter-actions { grid-column: 1 / -1; }
            }
            @media (max-width: 575.98px) {
                .delivery-filter-grid,
                .delivery-filter-grid.has-vendor { grid-template-columns: minmax(0, 1fr); }
                .delivery-filter-actions { grid-column: auto; }
            }
        </style>
    @endpush
@endonce

@php
    $deliveryTabs = [
        'assigned' => 'Pending',
        'in_transit' => 'In Transit',
        'delivered' => 'Delivered',
    ];
    $preservedFilters = array_filter(
        request()->only(['q', 'vendor_id', 'delivery_method', 'expected_date']),
        fn ($value) => $value !== null && $value !== ''
    );
@endphp

<nav class="delivery-tabs mb-3" aria-label="Delivery status">
    @foreach($deliveryTabs as $tabStatus => $tabLabel)
        <a
            href="{{ route($indexRoute, array_merge($preservedFilters, ['status' => $tabStatus])) }}"
            class="delivery-tab {{ $status === $tabStatus ? 'active' : '' }}"
            @if($status === $tabStatus) aria-current="page" @endif
        >
            {{ $tabLabel }}
            <span class="delivery-tab-count" data-delivery-status-count="{{ $tabStatus }}">{{ $tabCounts[$tabStatus] }}</span>
        </a>
    @endforeach
</nav>

<form method="GET" action="{{ route($indexRoute) }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="delivery-filter-grid {{ isset($vendors) ? 'has-vendor' : '' }}">
            <div>
                <label class="form-label small fw-semibold mb-1" for="delivery-search">Search</label>
                <input
                    class="form-control"
                    id="delivery-search"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="{{ $searchPlaceholder }}"
                >
            </div>
            @isset($vendors)
                <div>
                    <label class="form-label small fw-semibold mb-1" for="delivery-vendor">Vendor</label>
                    <select class="form-select" id="delivery-vendor" name="vendor_id">
                        <option value="">All vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) ($filters['vendor_id'] ?? '') === (string) $vendor->id)>
                                {{ $vendor->business_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endisset
            <div>
                <label class="form-label small fw-semibold mb-1" for="delivery-method">Delivery Method</label>
                <select class="form-select" id="delivery-method" name="delivery_method">
                    <option value="">All methods</option>
                    @foreach(config('marketplace.delivery_methods') as $method => $label)
                        <option value="{{ $method }}" @selected(($filters['delivery_method'] ?? '') === $method)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small fw-semibold mb-1" for="delivery-expected-date">Expected Date</label>
                <input class="form-control" id="delivery-expected-date" name="expected_date" type="date" value="{{ $filters['expected_date'] ?? '' }}">
            </div>
            <div class="delivery-filter-actions">
                <button class="btn btn-primary" type="submit">Apply</button>
                <a class="small text-decoration-none" href="{{ route($indexRoute, ['status' => $status]) }}">Reset</a>
            </div>
        </div>
    </div>
</form>
