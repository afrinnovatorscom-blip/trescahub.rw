<form method="POST" action="{{ route('marketplace.orders.save-changes', $order) }}" id="order-allocation-form" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
    @csrf
    <input type="hidden" name="allocate_order" value="yes">
    <input type="hidden" name="has_paid" value="no">
    <input type="hidden" name="submission_key" value="{{ old('submission_key', \Illuminate\Support\Str::uuid()) }}">

    <section class="detail-panel overflow-hidden mb-3" aria-labelledby="ordered-products">
        <div class="p-3 border-bottom">
            <h2 id="ordered-products" class="h5 mb-0">Review Order Items</h2>
        </div>
        <div class="table-responsive">
            <table class="table order-items-table align-middle mb-0">
                <thead>
                    <tr><th>Product</th><th>Quantity</th><th class="text-end">Unit price</th><th class="text-end">Line total</th></tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $item->product_name }}</div>
                                @if($item->sku)<div class="small text-muted">SKU: {{ $item->sku }}</div>@endif
                            </td>
                            <td>{{ number_format($item->quantity) }}</td>
                            <td class="text-end">{{ number_format((float) $item->unit_price, 0, '.', ',') }} RWF</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->line_total, 0, '.', ',') }} RWF</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-end">Order total</th><th class="text-end">{{ number_format((float) $order->total, 0, '.', ',') }} RWF</th></tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="detail-panel p-3 mb-3" aria-labelledby="order-allocation-title">
        <h2 id="order-allocation-title" class="h5 mb-1">Allocate the Entire Order</h2>
        <p class="small text-muted">Choose one active vendor to handle every product, or keep the entire order with Admin.</p>
        <label for="order-allocation" class="form-label">Order responsibility</label>
        <select id="order-allocation" name="order_allocation" class="form-select" required>
            <option value="" disabled @selected(old('order_allocation') === null)>Choose vendor or handling</option>
            <option value="admin" @selected(old('order_allocation') === 'admin')>Handle by Admin</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('order_allocation') === (string) $vendor->id)>
                    {{ $vendor->business_name }}
                </option>
            @endforeach
        </select>
        @error('order_allocation')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
        @error('allocations')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
        @error('order')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
        <div class="d-flex justify-content-end mt-3">
            <a href="{{ route('marketplace.orders.show', $order) }}" class="btn btn-outline-secondary me-2">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Order Allocation</button>
        </div>
    </section>
</form>
