<form method="POST" action="{{ route('marketplace.orders.save-changes', $order) }}" id="order-allocation-form" data-orders-live-form>
    @csrf
    <input type="hidden" name="allocate_order" value="yes">
    <input type="hidden" name="has_paid" value="no">
    <input type="hidden" name="submission_key" value="{{ old('submission_key', \Illuminate\Support\Str::uuid()) }}">
    <input type="hidden" name="order_allocation" id="selected-order-allocation">

    <section class="review-section mb-3" aria-labelledby="order-allocation-title">
        <div class="review-section-heading">
            <span class="review-section-icon" aria-hidden="true"><i class="fa-solid fa-user-shield"></i></span>
            <div>
                <h2 id="order-allocation-title" class="h5 mb-1">Who will handle this order?</h2>
                <p class="text-muted mb-0">The responsibility applies to the entire order.</p>
            </div>
        </div>

        <div class="allocation-options" role="radiogroup" aria-label="Order responsibility">
            <label class="allocation-choice">
                <input type="radio" name="handling_choice" value="admin" @checked(old('order_allocation') === 'admin') required>
                <span class="allocation-choice-mark" aria-hidden="true"><i class="fa-solid fa-building-shield"></i></span>
                <span>
                    <strong>Admin</strong>
                    <small>Accept, cancel, and record payment under your responsibility.</small>
                </span>
            </label>
            <label class="allocation-choice">
                <input type="radio" name="handling_choice" value="vendor" @checked(old('order_allocation') && old('order_allocation') !== 'admin') required>
                <span class="allocation-choice-mark" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
                <span>
                    <strong>Vendor</strong>
                    <small>Transfer the complete order to one selected vendor.</small>
                </span>
            </label>
        </div>

        <div id="vendor-allocation-field" class="vendor-allocation-field" hidden>
            <label for="allocation-vendor-id" class="form-label">Vendor</label>
            <select id="allocation-vendor-id" name="allocation_vendor_id" class="form-select">
                <option value="">Select an active vendor</option>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}" @selected((string) old('allocation_vendor_id') === (string) $vendor->id)>{{ $vendor->business_name }}</option>
                @endforeach
            </select>
            @error('allocation_vendor_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        @error('order_allocation')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        @error('allocations')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
        @error('order')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror

        <div class="review-actions">
            <a href="{{ route('marketplace.orders.index', ['tab' => 'pending']) }}" class="btn btn-light">Back</a>
            <button type="submit" class="btn btn-primary" id="confirm-allocation">Confirm Allocation</button>
        </div>
    </section>
</form>

<script>
    (() => {
        const updateAllocationChoice = () => {
            const vendorChoice = document.querySelector('#order-allocation-form input[name="handling_choice"][value="vendor"]');
            const adminChoice = document.querySelector('#order-allocation-form input[name="handling_choice"][value="admin"]');
            const vendorField = document.getElementById('vendor-allocation-field');
            const vendorSelect = document.getElementById('allocation-vendor-id');
            if (!vendorChoice || !adminChoice || !vendorField || !vendorSelect) return;

            const selectedVendor = vendorChoice.checked;
            vendorField.hidden = !selectedVendor;
            vendorSelect.required = selectedVendor;
            document.querySelectorAll('#order-allocation-form .allocation-choice').forEach((choice) => {
                    const input = choice.querySelector('input[name="handling_choice"]');
                    choice.classList.toggle('is-selected', input?.checked);
            });
        };

        document.getElementById('order-allocation-form')?.addEventListener('change', updateAllocationChoice);
        updateAllocationChoice();

        document.getElementById('order-allocation-form')?.addEventListener('submit', (event) => {
            const vendorChoice = document.querySelector('#order-allocation-form input[name="handling_choice"][value="vendor"]');
            const vendorSelect = document.getElementById('allocation-vendor-id');
            const hiddenChoice = document.getElementById('selected-order-allocation');
            if (hiddenChoice) hiddenChoice.value = vendorChoice?.checked ? vendorSelect.value : 'admin';
        });
    })();
</script>
