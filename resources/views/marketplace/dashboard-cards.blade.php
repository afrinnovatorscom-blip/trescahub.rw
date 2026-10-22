@php
    use App\Models\DeliveryTask;
    use App\Models\Order;
    use App\Models\ProductRequest;
    use App\Models\ShopProduct;
    use App\Models\Vendor;
    $user = auth()->user();
    $from = request('from');
    $to = request('to');
    $between = function ($query, $column = 'created_at') use ($from, $to) {
        if ($from) { $query->whereDate($column, '>=', $from); }
        if ($to) { $query->whereDate($column, '<=', $to); }
        return $query;
    };
@endphp
@if($user->hasPermission('admin.access'))
<form class="row g-2 mb-3" method="GET">
    <div class="col-auto"><input type="date" name="from" value="{{ $from }}" class="form-control form-control-sm" aria-label="From date"></div>
    <div class="col-auto"><input type="date" name="to" value="{{ $to }}" class="form-control form-control-sm" aria-label="To date"></div>
    <div class="col-auto"><button class="btn btn-sm btn-dark">Filter</button></div>
</form>
<div class="row g-3 mb-4">
    @foreach([
        ['Total products', ShopProduct::count()],
        ['Pending approvals', ShopProduct::where(function ($q) { $q->where('approval_status', 'pending_approval')->orWhere('revision_status', 'pending_approval'); })->count()],
        ['Published products', ShopProduct::publicCatalog()->count()],
        ['Active vendors', $user->hasPermission('vendors.view') ? Vendor::where('status', 'active')->count() : null],
        ['Pending requests', $user->hasAnyPermission(['requests.view_all','requests.view_assigned']) ? $between(ProductRequest::query())->where('status', 'submitted')->count() : null],
        ['Awaiting allocation', $user->hasPermission('requests.view_all') ? $between(ProductRequest::query())->whereNull('vendor_id')->whereIn('status', ['submitted','under_review','sourcing'])->count() : null],
        ['Active orders', $user->hasAnyPermission(['orders.view_all','orders.view_assigned']) ? $between(Order::query())->whereNotIn('fulfillment_status', ['completed','cancelled','returned'])->count() : null],
        ['Awaiting fulfillment', $user->hasPermission('orders.view_all') ? $between(Order::query())->whereIn('fulfillment_status', ['pending_confirmation','confirmed','processing','awaiting_stock','ready_for_dispatch'])->count() : null],
        ['In transit', $user->hasPermission('orders.view_all') ? $between(Order::query())->whereIn('fulfillment_status', ['dispatched','out_for_delivery'])->count() : null],
        ['Completed orders', $user->hasPermission('orders.view_all') ? $between(Order::query())->where('fulfillment_status', 'completed')->count() : null],
        ['Low stock', $user->hasPermission('inventory.view') ? ShopProduct::where('tracks_inventory', true)->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold')->count() : null],
        ['Delayed deliveries', $user->hasPermission('deliveries.manage') ? DeliveryTask::whereNotIn('status', ['delivered','cancelled'])->where('created_at', '<', now()->subDays(3))->count() : null],
    ] as [$label, $value])
        @if(!is_null($value))
            <div class="col-6 col-lg-3">
                <div class="dash-card p-3 h-100">
                    <div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:700;">{{ $value }}</div>
                    <div class="text-muted small">{{ $label }}</div>
                </div>
            </div>
        @endif
    @endforeach
</div>
<div class="row g-3 mb-4">
    @if($user->hasPermission('products.approve'))
        <div class="col-lg-6">
            <div class="dash-card p-3">
                <h2 class="h6">Recent submissions</h2>
                <ul class="list-group list-group-flush">
                    @forelse(ShopProduct::with('submitter')->where('approval_status', 'pending_approval')->latest()->limit(5)->get() as $product)
                        <li class="list-group-item px-0">{{ $product->product_name }} <span class="text-muted small">{{ $product->submitter->name ?? 'Staff' }}</span></li>
                    @empty
                        <li class="list-group-item px-0 text-muted">No products are waiting.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
    @if($user->hasPermission('requests.view_all'))
        <div class="col-lg-6">
            <div class="dash-card p-3">
                <h2 class="h6">Unallocated requests</h2>
                <ul class="list-group list-group-flush">
                    @forelse(ProductRequest::whereNull('vendor_id')->whereNotIn('status', ['completed','cancelled','rejected','converted_to_order'])->latest()->limit(5)->get() as $record)
                        <li class="list-group-item px-0"><a href="{{ route('marketplace.requests.show', $record) }}">{{ $record->reference }}</a> — {{ $record->product_name }}</li>
                    @empty
                        <li class="list-group-item px-0 text-muted">Every request has an owner.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
@endif
