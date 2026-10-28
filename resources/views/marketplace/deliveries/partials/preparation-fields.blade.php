<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="delivery_method">Delivery Method <span class="text-danger" aria-hidden="true">*</span></label>
        <select class="form-select @error('delivery_method') is-invalid @enderror" id="delivery_method" name="delivery_method" required>
            <option value="">Select a delivery method</option>
            @foreach(config('marketplace.delivery_methods') as $method => $label)
                <option value="{{ $method }}" @selected(old('delivery_method', $delivery?->delivery_method) === $method)>{{ $label }}</option>
            @endforeach
        </select>
        @error('delivery_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="courier_name">Driver / Courier Name</label>
        <input class="form-control @error('courier_name') is-invalid @enderror" id="courier_name" name="courier_name" value="{{ old('courier_name', $delivery?->courier_name) }}" maxlength="150" data-required-methods="in_house,third_party_courier">
        @error('courier_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="delivery_phone">Delivery Phone</label>
        <input class="form-control @error('delivery_phone') is-invalid @enderror" id="delivery_phone" name="delivery_phone" type="tel" value="{{ old('delivery_phone', $delivery?->delivery_phone) }}" maxlength="40" data-required-methods="in_house,third_party_courier">
        @error('delivery_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="vehicle_registration">Vehicle Plate</label>
        <input class="form-control @error('vehicle_registration') is-invalid @enderror" id="vehicle_registration" name="vehicle_registration" value="{{ old('vehicle_registration', $delivery?->vehicle_registration) }}" maxlength="40" data-required-methods="in_house">
        @error('vehicle_registration')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="expected_delivery_at">Expected Delivery Date</label>
        <input class="form-control @error('expected_delivery_at') is-invalid @enderror" id="expected_delivery_at" name="expected_delivery_at" type="date" min="{{ now()->toDateString() }}" value="{{ old('expected_delivery_at', $delivery?->expected_delivery_at?->toDateString()) }}">
        @error('expected_delivery_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="tracking_reference">Tracking / Dispatch Reference</label>
        <input class="form-control @error('tracking_reference') is-invalid @enderror" id="tracking_reference" name="tracking_reference" value="{{ old('tracking_reference', $delivery?->tracking_reference) }}" maxlength="100">
        @error('tracking_reference')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="delivery_notes">Notes</label>
        <textarea class="form-control @error('delivery_notes') is-invalid @enderror" id="delivery_notes" name="delivery_notes" rows="3" maxlength="2000">{{ old('delivery_notes', $delivery?->delivery_notes) }}</textarea>
        @error('delivery_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label" for="dispatch_proof">Dispatch Proof / Courier Ticket</label>
        <input class="form-control @error('dispatch_proof') is-invalid @enderror" id="dispatch_proof" name="dispatch_proof" type="file" accept=".jpg,.jpeg,.png,.pdf">
        @error('dispatch_proof')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">PDF, JPG or PNG up to 4 MB. This records dispatch documentation, not proof of delivery.</div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-preparation-form]').forEach((form) => {
                const method = form.querySelector('#delivery_method');
                const conditionalFields = form.querySelectorAll('[data-required-methods]');
                const submit = form.querySelector('[data-preparation-submit]');

                const updateRequiredFields = () => {
                    conditionalFields.forEach((field) => {
                        const requiredMethods = field.dataset.requiredMethods.split(',');
                        field.required = requiredMethods.includes(method.value);
                    });
                };

                method.addEventListener('change', updateRequiredFields);
                updateRequiredFields();
                form.addEventListener('submit', () => {
                    submit.disabled = true;
                    submit.textContent = 'Saving...';
                });
            });
        </script>
    @endpush
@endonce
