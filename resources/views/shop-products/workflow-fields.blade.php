<div class="row gx-3">
    <div class="col-12 product-form-field">
        <label class="form-label" for="vendor_id">Assign Product to Vendor</label>
        <div class="form-text mb-2">Select the vendor responsible for this product, or choose Tresca Direct if the product is managed directly by Tresca Hub.</div>
        <select id="vendor_id" name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="" @selected(old('vendor_id', $product->vendor_id ?? '') == '')>TRESCA direct / not assigned</option>
            @foreach(($vendors ?? collect()) as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id ?? '') == $vendor->id)>
                    {{ $vendor->business_name }} ({{ $vendor->status }})
                </option>
            @endforeach
        </select>
        @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
