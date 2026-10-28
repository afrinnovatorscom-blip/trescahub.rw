<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <section class="review-section h-100" aria-labelledby="admin-order-decision">
            <div class="review-section-heading">
                <span class="review-section-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                <div>
                    <h2 id="admin-order-decision" class="h5 mb-1">Order decision</h2>
                    <p class="text-muted mb-0">Choose how to complete this review.</p>
                </div>
            </div>

            <div class="decision-options">
                <button type="button" class="decision-choice accept-choice {{ old('decision') === 'reject' ? '' : 'is-selected' }}" id="choose-accept-order" aria-pressed="{{ old('decision') === 'reject' ? 'false' : 'true' }}">
                    <span class="decision-choice-icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                    <span><strong>Accept order</strong><small>Continue with payment</small></span>
                </button>
                <button type="button" class="decision-choice cancel-choice {{ old('decision') === 'reject' ? 'is-selected' : '' }}" id="choose-cancel-order" aria-pressed="{{ old('decision') === 'reject' ? 'true' : 'false' }}">
                    <span class="decision-choice-icon" aria-hidden="true"><i class="fa-solid fa-xmark"></i></span>
                    <span><strong>Cancel order</strong><small>End this order and record why</small></span>
                </button>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <form method="POST" action="{{ route('marketplace.orders.review', $order) }}" id="cancel-order-form" data-orders-live-form @if(old('decision') !== 'reject') hidden @endif>
            @csrf
            <input type="hidden" name="decision" value="reject">
            <section class="review-section h-100" aria-labelledby="cancel-order-title">
                <h2 id="cancel-order-title" class="h6 mb-2">Cancellation reason</h2>
                <label for="cancellation-reason" class="form-label" id="cancellation-reason-label"></label>
                <textarea id="cancellation-reason" name="reason" class="form-control" rows="4" maxlength="230" required>{{ old('reason') }}</textarea>
                @error('reason')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                <div class="review-actions mt-3">
                    <button type="button" class="btn btn-light" id="back-to-accept">Back</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </section>
        </form>

        <form method="POST" action="{{ route('marketplace.orders.save-changes', $order) }}" id="accept-order-form" data-orders-live-form @if(old('decision') === 'reject') hidden @endif>
            @csrf
            <input type="hidden" name="accept_order" value="yes">
            <input type="hidden" name="submission_key" value="{{ old('submission_key', \Illuminate\Support\Str::uuid()) }}">
            <section class="review-section h-100" aria-labelledby="payment-information">
                <div class="review-section-heading compact-heading">
                    <span class="review-section-icon" aria-hidden="true"><i class="fa-solid fa-wallet"></i></span>
                    <div>
                        <h2 id="payment-information" class="h6 mb-0">Payment information</h2>
                    </div>
                </div>

                <fieldset class="mt-3">
                    <legend class="form-label">Has the customer paid?</legend>
                    <div class="segmented-control">
                        <label><input type="radio" name="has_paid" value="no" id="has-not-paid" @checked(old('has_paid', 'no') === 'no')> <span>No</span></label>
                        <label><input type="radio" name="has_paid" value="yes" id="has-paid" @checked(old('has_paid') === 'yes') @disabled($outstandingBalance <= 0)> <span>Yes</span></label>
                    </div>
                </fieldset>

                <div id="payment-details" class="payment-details" hidden>
                    <fieldset class="mt-3">
                        <legend class="form-label">Payment type</legend>
                        <div class="payment-options">
                            <label><input type="radio" name="payment_type" value="full" @checked(old('payment_type', 'full') === 'full')> <span><i class="fa-solid fa-credit-card" aria-hidden="true"></i> Full payment</span></label>
                            <label><input type="radio" name="payment_type" value="partial" @checked(old('payment_type') === 'partial')> <span><i class="fa-solid fa-coins" aria-hidden="true"></i> Partial payment</span></label>
                        </div>
                    </fieldset>

                    <label for="payment-method" class="form-label mt-3">Payment method</label>
                    <select id="payment-method" name="payment_method" class="form-select">
                        <option value="cash_on_delivery" @selected(old('payment_method', $order->payment_method) === 'cash_on_delivery')>Cash</option>
                        <option value="bank_transfer" @selected(old('payment_method', $order->payment_method) === 'bank_transfer')>Bank transfer</option>
                    </select>

                    <div id="partial-payment-amount" class="mt-3" hidden>
                        <label for="partial-amount" class="form-label">Amount paid</label>
                        <div class="input-group">
                            <input id="partial-amount" name="partial_amount" type="number" min="0.01" max="{{ number_format(max(0, round($outstandingBalance - 0.01, 2)), 2, '.', '') }}" step="0.01" value="{{ old('partial_amount') }}" class="form-control" inputmode="decimal">
                            <span class="input-group-text">RWF</span>
                        </div>
                    </div>
                </div>

                <dl class="balance-summary mt-3 mb-0">
                    <div><dt>Order total</dt><dd>{{ number_format((float) $order->total, 2, '.', ',') }} RWF</dd></div>
                    <div><dt>Remaining balance</dt><dd id="remaining-balance" data-total="{{ number_format((float) $order->total, 2, '.', '') }}">{{ number_format($outstandingBalance, 2, '.', ',') }} RWF</dd></div>
                </dl>

                @error('accept_order')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('has_paid')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('payment_type')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror
                @error('partial_amount')<div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>@enderror

                <div class="review-actions mt-3">
                    <a href="{{ route('marketplace.orders.index', ['tab' => 'pending']) }}" class="btn btn-light">Back</a>
                    <button type="submit" class="btn btn-primary">Accept Order</button>
                </div>
            </section>
        </form>
    </div>
</div>
