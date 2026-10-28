@extends('front.layouts.admin')
@section('page-title', 'Inventory')
@section('content')
<style>
    .vendor-inventory-page{max-width:1280px}
    .vendor-inventory-surface{border:1px solid #e2e8f0;border-radius:10px;background:#fff}
    .vendor-inventory-head,.vendor-inventory-body{padding:18px 20px}
    .vendor-inventory-head{border-bottom:1px solid #e2e8f0}
    .vendor-stock-row{padding:16px 0;border-bottom:1px solid #eef1f5}
    .vendor-stock-row:last-child{border-bottom:0;padding-bottom:0}
    @media(max-width:575.98px){.vendor-inventory-head,.vendor-inventory-body{padding:14px}}
</style>
<div class="container-fluid py-4 vendor-inventory-page">
    @include('marketplace.partials.flash')
    <div class="mb-4">
        <div class="small text-muted mb-1">Marketplace / Inventory</div>
        <h1 class="h3 mb-1">Your Inventory</h1>
        <p class="text-muted mb-0">Request stock changes for administrator approval. Your approved stock remains unchanged until review.</p>
    </div>
    @if($errors->any())<div class="alert alert-danger" role="alert">Please correct the inventory request errors below.</div>@endif

    <section class="vendor-inventory-surface mb-4">
        <div class="vendor-inventory-head"><h2 class="h5 mb-1">Tracked Products</h2><p class="small text-muted mb-0">Reductions cannot use stock reserved for orders. Corrections request a target stock quantity.</p></div>
        <div class="vendor-inventory-body">
            @forelse($products as $product)
                <div class="vendor-stock-row">
                    <div class="row g-3 align-items-start">
                        <div class="col-lg-3">
                            <div class="fw-semibold">{{ $product->product_name }}</div>
                            <div class="small text-muted">{{ $product->sku ?? 'SKU pending' }}</div>
                            <div class="small mt-2">On hand: <strong>{{ number_format($product->stock_quantity) }} {{ $product->stock_unit }}</strong></div>
                            <div class="small text-muted">Reserved: {{ number_format($product->reserved_quantity) }} {{ $product->stock_unit }} · Available: {{ number_format($product->availableQuantity()) }} {{ $product->stock_unit }}</div>
                        </div>
                        <div class="col-lg-9">
                            <form method="POST" action="{{ route('vendor.inventory.adjust', $product->slug) }}" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-sm-4">
                                    <label class="form-label small fw-semibold" for="change_type_{{ $product->id }}">Change type</label>
                                    <select id="change_type_{{ $product->id }}" name="change_type" class="form-select form-select-sm" required>
                                        <option value="addition">Add stock</option>
                                        <option value="reduction">Reduce stock</option>
                                        <option value="correction">Correct to a stock total</option>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold" for="quantity_{{ $product->id }}" data-quantity-label>Quantity / target</label>
                                    <input id="quantity_{{ $product->id }}" name="quantity" type="number" min="1" max="2147483647" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label small fw-semibold" for="reason_{{ $product->id }}">Reason</label>
                                    <input id="reason_{{ $product->id }}" name="reason" maxlength="1000" class="form-control form-control-sm" required placeholder="Why is this change needed?">
                                </div>
                                <div class="col-sm-2"><button class="btn btn-sm btn-primary w-100" type="submit">Request</button></div>
                            </form>
                            @error('product')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            @error('quantity')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            @error('change_type')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            @error('reason')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No tracked products are assigned to your vendor account.</div>
            @endforelse
        </div>
    </section>

    <section class="vendor-inventory-surface">
        <div class="vendor-inventory-head"><h2 class="h5 mb-1">Your Stock Change Requests</h2><p class="small text-muted mb-0">Pending requests do not change approved stock. Review outcomes appear here.</p></div>
        <div class="vendor-inventory-body">
            <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                <thead><tr><th>Product</th><th>Requested change</th><th>Reason</th><th>Status</th><th>Review</th><th>Submitted</th></tr></thead>
                <tbody>
                @forelse($requests as $inventoryRequest)
                    <tr>
                        <td>{{ $inventoryRequest->product?->product_name ?? 'Deleted product' }}<div class="small text-muted">{{ $inventoryRequest->product?->sku ?? '—' }}</div></td>
                        <td>
                            @if($inventoryRequest->change_type === 'addition') Add {{ number_format($inventoryRequest->quantity) }}
                            @elseif($inventoryRequest->change_type === 'reduction') Reduce {{ number_format($inventoryRequest->quantity) }}
                            @else Set total to {{ number_format($inventoryRequest->quantity) }}
                            @endif {{ $inventoryRequest->product?->stock_unit }}
                        </td>
                        <td>{{ $inventoryRequest->reason }}</td>
                        <td><span class="badge {{ $inventoryRequest->status === 'approved' ? 'text-bg-success' : ($inventoryRequest->status === 'pending' ? 'text-bg-warning' : 'text-bg-danger') }}">{{ ucfirst($inventoryRequest->status) }}</span></td>
                        <td>{{ $inventoryRequest->reviewer?->name ?? '—' }}
                            @if($inventoryRequest->review_note)<div class="small text-muted">{{ $inventoryRequest->review_note }}</div>@endif
                            @if($inventoryRequest->reviewed_at)<div class="small text-muted">{{ $inventoryRequest->reviewed_at->format('Y-m-d H:i') }}</div>@endif
                        </td>
                        <td>{{ $inventoryRequest->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">You have not submitted stock change requests.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="mt-3">{{ $requests->links() }}</div>
        </div>
    </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[action*="/vendor/inventory/"]').forEach(function (form) {
        const type = form.querySelector('[name="change_type"]');
        const label = form.querySelector('[data-quantity-label]');
        function updateLabel() {
            label.textContent = type.value === 'correction' ? 'Target stock total' : 'Quantity to change';
        }
        type.addEventListener('change', updateLabel);
        updateLabel();
    });
});
</script>
@endsection
