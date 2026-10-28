@php
    $vendorOwnsCompleteOrder = $items->isNotEmpty()
        && $items->count() === $order->items->count()
        && $items->every(fn ($item) => $item->assignment_status === 'assigned');
@endphp
@if($vendorOwnsCompleteOrder)
    <section class="vendor-review-section mb-3" aria-labelledby="vendor-order-decision">
        <div class="vendor-review-heading">
            <span class="vendor-review-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
            <div>
                <h2 id="vendor-order-decision" class="h5 mb-1">Order decision</h2>
                <p class="text-muted mb-0">Accept the complete assigned order or cancel it with a reason.</p>
            </div>
        </div>

        <div class="vendor-decision-grid">
            <button type="button" class="vendor-decision-choice is-selected" id="vendor-accept-order" aria-pressed="true">
                <span class="vendor-decision-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                <span><strong>Accept order</strong><small>Continue with fulfillment</small></span>
            </button>
            <button type="button" class="vendor-decision-choice vendor-cancel-choice" id="vendor-cancel-order" aria-pressed="false">
                <span class="vendor-decision-icon" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                <span><strong>Cancel order</strong><small>Return it for reassignment</small></span>
            </button>
        </div>

        <form method="POST" action="{{ route('vendor.orders.respond-order', $order->reference) }}" id="vendor-accept-order-form" data-orders-live-form>
            @csrf
            <input type="hidden" name="decision" value="accept">
            <input type="hidden" name="submission_key" value="{{ old('submission_key', \Illuminate\Support\Str::uuid()) }}">

            <div class="vendor-payment-panel mt-3">
                <div class="vendor-review-heading mb-3">
                    <span class="vendor-review-icon" aria-hidden="true"><i class="fa-solid fa-wallet"></i></span>
                    <div><h3 class="h6 mb-0">Payment information</h3></div>
                </div>

                <fieldset>
                    <legend class="form-label">Has the customer paid?</legend>
                    <div class="vendor-segmented-control">
                        <label><input type="radio" name="has_paid" value="no" id="vendor-has-not-paid" @checked(old('has_paid', 'no') === 'no')> <span>No</span></label>
                        <label><input type="radio" name="has_paid" value="yes" id="vendor-has-paid" @checked(old('has_paid') === 'yes') @disabled($outstandingBalance <= 0)> <span>Yes</span></label>
                    </div>
                </fieldset>

                <div id="vendor-payment-details" class="mt-3" hidden>
                    <fieldset>
                        <legend class="form-label">Payment type</legend>
                        <div class="vendor-payment-options">
                            <label><input type="radio" name="payment_type" value="full" @checked(old('payment_type', 'full') === 'full')> <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Full payment</span></label>
                            <label><input type="radio" name="payment_type" value="partial" @checked(old('payment_type') === 'partial')> <span><i class="fa-solid fa-coins" aria-hidden="true"></i> Partial payment</span></label>
                        </div>
                    </fieldset>

                    <label for="vendor-payment-method" class="form-label mt-3">Payment method</label>
                    <select id="vendor-payment-method" name="payment_method" class="form-select">
                        <option value="cash_on_delivery" @selected(old('payment_method', $order->payment_method) === 'cash_on_delivery')>Cash</option>
                        <option value="bank_transfer" @selected(old('payment_method', $order->payment_method) === 'bank_transfer')>Bank transfer</option>
                    </select>

                    <div id="vendor-partial-payment-amount" class="mt-3" hidden>
                        <label for="vendor-partial-amount" class="form-label">Amount paid</label>
                        <div class="input-group">
                            <input id="vendor-partial-amount" name="partial_amount" type="number" min="0.01" max="{{ number_format(max(0, round($outstandingBalance - 0.01, 2)), 2, '.', '') }}" step="0.01" value="{{ old('partial_amount') }}" class="form-control" inputmode="decimal">
                            <span class="input-group-text">RWF</span>
                        </div>
                    </div>
                </div>

                <dl class="vendor-balance-summary mt-3 mb-0">
                    <div><dt>Order total</dt><dd>{{ number_format((float) $order->total, 2, '.', ',') }} RWF</dd></div>
                    <div><dt>Remaining balance</dt><dd id="vendor-remaining-balance" data-total="{{ number_format((float) $outstandingBalance, 2, '.', '') }}">{{ number_format($outstandingBalance, 2, '.', ',') }} RWF</dd></div>
                </dl>

                @error('has_paid')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('payment_type')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('payment_method')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('partial_amount')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
            </div>

            <div class="vendor-review-actions">
                <a href="{{ route('vendor.orders.index', ['tab' => 'pending']) }}" class="btn btn-light">Back</a>
                <button type="submit" class="btn btn-primary">Accept Order</button>
            </div>
        </form>

        <form method="POST" action="{{ route('vendor.orders.respond-order', $order->reference) }}" id="vendor-cancel-order-form" data-orders-live-form hidden>
            @csrf
            <input type="hidden" name="decision" value="decline">
            <label for="vendor-cancellation-reason" class="form-label mt-3">Cancellation reason <span class="text-danger" aria-hidden="true">*</span></label>
            <textarea id="vendor-cancellation-reason" name="note" class="form-control" rows="3" maxlength="230" required placeholder="Explain why this order cannot be fulfilled"></textarea>
            @error('note')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            <div class="vendor-review-actions">
                <button type="button" class="btn btn-light" id="vendor-back-to-accept">Back</button>
                <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const acceptChoice = document.getElementById('vendor-accept-order');
            const cancelChoice = document.getElementById('vendor-cancel-order');
            const acceptForm = document.getElementById('vendor-accept-order-form');
            const cancelForm = document.getElementById('vendor-cancel-order-form');
            const reason = document.getElementById('vendor-cancellation-reason');
            const paidRadios = acceptForm.querySelectorAll('input[name="has_paid"]');
            const paymentDetails = document.getElementById('vendor-payment-details');
            const paymentTypeRadios = acceptForm.querySelectorAll('input[name="payment_type"]');
            const partialAmount = document.getElementById('vendor-partial-payment-amount');
            const partialInput = document.getElementById('vendor-partial-amount');
            const remainingBalance = document.getElementById('vendor-remaining-balance');
            if (!acceptChoice || !cancelChoice || !acceptForm || !cancelForm || !reason || !paymentDetails) return;

            const showCancel = (visible) => {
                acceptChoice.classList.toggle('is-selected', !visible);
                cancelChoice.classList.toggle('is-selected', visible);
                acceptChoice.setAttribute('aria-pressed', visible ? 'false' : 'true');
                cancelChoice.setAttribute('aria-pressed', visible ? 'true' : 'false');
                acceptForm.hidden = visible;
                cancelForm.hidden = !visible;
                reason.required = visible;
                if (visible) reason.focus();
            };

            const renderPayment = () => {
                const showPayment = acceptForm.querySelector('input[name="has_paid"]:checked')?.value === 'yes';
                const showPartial = showPayment && acceptForm.querySelector('input[name="payment_type"]:checked')?.value === 'partial';
                paymentDetails.hidden = !showPayment;
                partialAmount.hidden = !showPartial;
                partialInput.required = showPartial;

                const outstanding = Number(remainingBalance.dataset.total || 0);
                const amount = showPartial ? Math.min(outstanding, Number(partialInput.value || 0)) : outstanding;
                const balance = showPayment ? Math.max(0, outstanding - amount) : outstanding;
                remainingBalance.textContent = `${balance.toFixed(2)} RWF`;
            };

            paidRadios.forEach((radio) => radio.addEventListener('change', renderPayment));
            paymentTypeRadios.forEach((radio) => radio.addEventListener('change', renderPayment));
            partialInput?.addEventListener('input', renderPayment);
            acceptChoice.addEventListener('click', () => showCancel(false));
            cancelChoice.addEventListener('click', () => showCancel(true));
            document.getElementById('vendor-back-to-accept')?.addEventListener('click', () => showCancel(false));
            renderPayment();
        })();
    </script>
@endif
