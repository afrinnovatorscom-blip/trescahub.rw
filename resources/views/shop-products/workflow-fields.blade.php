<div class="row gx-3">
    <div class="col-md-6 product-form-field">
        <label class="form-label" for="vendor_id">Assign to Vendor</label>
        <select id="vendor_id" name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror">
            <option value="">TRESCA direct / not assigned</option>
            @foreach(($vendors ?? collect()) as $vendor)
                <option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id ?? '') == $vendor->id)>
                    {{ $vendor->business_name }} ({{ $vendor->status }})
                </option>
            @endforeach
        </select>
        @error('vendor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
