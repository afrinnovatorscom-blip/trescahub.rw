@php
    if (! isset($product)) {
        $product = new \App\Models\ShopProduct;
    }
@endphp
<hr>
<h2 class="h6">Catalog controls</h2>
<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label" for="sku">SKU</label>
        <input id="sku" name="sku" class="form-control" value="{{ old('sku', $product->sku ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="product_type">Product type</label>
        <input id="product_type" name="product_type" class="form-control" value="{{ old('product_type', $product->product_type ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="condition">Condition</label>
        <input id="condition" name="condition" class="form-control" value="{{ old('condition', $product->condition ?? '') }}">
    </div>
</div>
@if(auth()->user()->hasPermission('products.assign_vendor'))
    <div class="mb-3">
        <label class="form-label" for="vendor_id">Assigned vendor (internal)</label>
        <select id="vendor_id" name="vendor_id" class="form-select">
            <option value="">TRESCA direct / not assigned</option>
            @foreach(($vendors ?? collect()) as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id ?? '') == $vendor->id)>{{ $vendor->business_name }} ({{ $vendor->status }})</option>
            @endforeach
        </select>
    </div>
@endif
@if(auth()->user()->hasPermission('finance.view'))
    <div class="mb-3">
        <label class="form-label" for="acquisition_cost">Acquisition cost (RWF, internal)</label>
        <input id="acquisition_cost" type="number" min="0" step="1" name="acquisition_cost" class="form-control" value="{{ old('acquisition_cost', $product->acquisition_cost ?? '') }}">
    </div>
@endif
<div class="mb-3">
    <label class="form-label" for="internal_notes">Internal notes</label>
    <textarea id="internal_notes" name="internal_notes" class="form-control" rows="2">{{ old('internal_notes', $product->internal_notes ?? '') }}</textarea>
</div>
<div class="form-check mb-2">
    <input class="form-check-input" type="checkbox" name="tracks_inventory" value="1" id="tracks_inventory" @checked(old('tracks_inventory', $product->tracks_inventory ?? false))>
    <label class="form-check-label" for="tracks_inventory">Track stock quantities</label>
</div>
@if(auth()->user()->hasPermission('products.approve'))
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="approve_now" value="1" id="approve_now">
        <label class="form-check-label" for="approve_now">Approve now</label>
    </div>
@endif
@if(auth()->user()->hasPermission('products.publish'))
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="publish_now" value="1" id="publish_now">
        <label class="form-check-label" for="publish_now">Publish now (only after approval)</label>
    </div>
@endif
