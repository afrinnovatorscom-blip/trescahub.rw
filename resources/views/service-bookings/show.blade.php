@extends('front.layouts.admin')

@section('title', 'Review Service Booking')
@section('page-title', 'Review Service Booking')

@section('content')
@php
    $paymentLabel = match ($paymentStatus) {
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        default => 'Not Paid',
    };
    $hasSavedPayment = $serviceBooking->payment_recorded;
@endphp

<div class="booking-review-header">
    <div>
        <a class="booking-back-link" href="{{ route('service-bookings.index', ['tab' => $serviceBooking->status]) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Service Bookings
        </a>
        <h1 class="booking-review-title">Review booking <span class="booking-reference">{{ $serviceBooking->reference }}</span></h1>
        <p class="booking-review-subtitle mb-0">Review customer details, set the appointment, and record payment.</p>
    </div>
    <span class="booking-status-badge">{{ ucfirst($serviceBooking->status) }}</span>
</div>

<div class="booking-toast" id="booking-toast" role="status" aria-live="polite" hidden>
    <span class="booking-toast-icon" aria-hidden="true"></span>
    <span class="booking-toast-message"></span>
    <button type="button" class="booking-toast-close" aria-label="Dismiss notification">&times;</button>
</div>
@if(session('success'))
    <div data-booking-toast-message="{{ session('success') }}" data-booking-toast-type="success" hidden></div>
@endif
@if($errors->any())
    <div data-booking-toast-message="{{ $errors->first() }}" data-booking-toast-type="error" hidden></div>
@endif

<div class="booking-review-page">
    <section class="booking-review-section booking-information-section" aria-labelledby="booking-information-title">
        <div class="booking-section-heading">
            <span class="booking-section-icon"><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
            <div>
                <h2 id="booking-information-title">Booking information</h2>
                <p>Customer and service details</p>
            </div>
        </div>

        <dl class="booking-information-grid">
            <div>
                <dt>Customer</dt>
                <dd>{{ $serviceBooking->customer_name }}</dd>
            </div>
            <div>
                <dt>Service</dt>
                <dd>{{ $serviceBooking->service?->title ?? 'Service unavailable' }}</dd>
            </div>
            <div>
                <dt>Phone</dt>
                <dd>{{ $serviceBooking->customer_phone }}</dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $serviceBooking->customer_email ?: 'Not provided' }}</dd>
            </div>
            <div>
                <dt>Preferred date</dt>
                <dd>{{ $serviceBooking->preferred_date->format('M j, Y') }}</dd>
            </div>
            <div class="booking-requirements">
                <dt>Requirements</dt>
                <dd>{{ $serviceBooking->requirements ?: 'No additional requirements provided.' }}</dd>
            </div>
        </dl>
    </section>

    <section class="booking-review-section booking-appointment-section" aria-labelledby="booking-appointment-title">
        <div class="booking-section-heading">
            <span class="booking-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
            <div>
                <h2 id="booking-appointment-title">Appointment scheduling</h2>
                <p>Set or update the agreed appointment date</p>
            </div>
        </div>

        <form method="POST" action="{{ route('service-bookings.appointment', $serviceBooking) }}" class="booking-action-form">
            @csrf
            @method('PATCH')
            <div class="booking-date-layout">
                <div class="booking-preferred-date">
                    <span>Customer's preferred date</span>
                    <strong>{{ $serviceBooking->preferred_date->format('l, M j, Y') }}</strong>
                </div>
                <div class="booking-date-control">
                    <label for="appointment-date" class="booking-field-label">Actual appointment date</label>
                    <div class="booking-date-row">
                        <input
                            id="appointment-date"
                            name="appointment_date"
                            type="date"
                            class="form-control booking-input"
                            min="{{ now()->toDateString() }}"
                            value="{{ old('appointment_date', $serviceBooking->appointment_date?->format('Y-m-d')) }}"
                            required
                        >
                        <button type="submit" class="btn booking-secondary-button">Save date</button>
                    </div>
                    @if($serviceBooking->appointment_date)
                        <p class="booking-current-appointment">
                            Saved appointment: <strong>{{ $serviceBooking->appointment_date->format('M j, Y') }}</strong>
                            @if($serviceBooking->appointment_time)
                                · {{ ucfirst($serviceBooking->appointment_time) }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
            @error('appointment_date')<div class="booking-inline-error">{{ $message }}</div>@enderror
        </form>

    </section>

    <section class="booking-review-section booking-payment-section" aria-labelledby="booking-payment-title">
        <div class="booking-section-heading">
            <span class="booking-section-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <div>
                <h2 id="booking-payment-title">Payment recording</h2>
                <p>Record the amount received from the customer</p>
            </div>
            <span class="booking-payment-badge {{ $paymentStatus }}" id="payment-status-badge">{{ $paymentLabel }}</span>
        </div>

        <form
            id="booking-payment-form"
            action="{{ route('service-bookings.payment', $serviceBooking) }}"
            method="POST"
            data-total="{{ $serviceTotal ?? '' }}"
            data-saved="{{ $hasSavedPayment ? 'true' : 'false' }}"
            data-initial-status="{{ $paymentStatus }}"
            data-initial-amount="{{ $serviceBooking->payment_amount_received ?? '' }}"
        >
            @csrf
            @method('PATCH')
            <fieldset class="booking-choice-group">
                <legend>Has the customer paid?</legend>
                <label class="booking-choice">
                    <input type="radio" name="has_paid" value="yes" @checked(in_array($paymentStatus, ['paid', 'partially_paid'], true)) @disabled($hasSavedPayment)>
                    <span>Yes</span>
                </label>
                <label class="booking-choice">
                    <input type="radio" name="has_paid" value="no" @checked($paymentStatus === 'not_paid') @disabled($hasSavedPayment)>
                    <span>No</span>
                </label>
            </fieldset>

            <div class="booking-payment-details" id="payment-details" @if($paymentStatus === 'not_paid') hidden @endif>
                <fieldset class="booking-choice-group booking-payment-type">
                    <legend>Payment amount</legend>
                    <label class="booking-choice">
                        <input type="radio" name="payment_type" value="full" @checked($paymentStatus === 'paid') @disabled($hasSavedPayment)>
                        <span>Fully Paid</span>
                    </label>
                    <label class="booking-choice">
                        <input type="radio" name="payment_type" value="partial" @checked($paymentStatus === 'partially_paid') @disabled($hasSavedPayment)>
                        <span>Partially Paid</span>
                    </label>
                </fieldset>

                <div class="booking-money-input" id="amount-input-wrap" @if($paymentStatus !== 'partially_paid' && $serviceTotal !== null) hidden @endif>
                    <label for="amount-received" class="booking-field-label">Amount received</label>
                    <div class="input-group">
                        <span class="input-group-text booking-currency">RWF</span>
                        <input
                            id="amount-received"
                            name="amount_received"
                            type="number"
                            class="form-control booking-input"
                            min="0.01"
                            step="0.01"
                            max="{{ $serviceTotal }}"
                            value="{{ $serviceBooking->payment_amount_received }}"
                            inputmode="decimal"
                            placeholder="Enter amount received"
                            @disabled($hasSavedPayment)
                        >
                    </div>
                    <div class="booking-inline-error" id="amount-error" role="alert"></div>
                </div>
            </div>

            <div class="booking-amount-summary" aria-live="polite">
                <div><span>Total booking amount</span><strong id="booking-total">{{ $serviceTotal !== null ? 'RWF '.number_format((float) $serviceTotal, 0) : 'Unavailable' }}</strong></div>
                <div><span>Amount received</span><strong id="booking-received">{{ $serviceBooking->payment_amount_received !== null ? 'RWF '.number_format((float) $serviceBooking->payment_amount_received, 0) : ($paymentStatus === 'partially_paid' ? 'Not recorded' : 'RWF 0') }}</strong></div>
                <div><span>Remaining balance</span><strong id="booking-balance">{{ $serviceTotal === null ? 'Unavailable' : ($paymentStatus === 'partially_paid' && $serviceBooking->payment_amount_received === null ? 'Not recorded' : 'RWF '.number_format(max(0, (float) $serviceTotal - (float) ($serviceBooking->payment_amount_received ?? 0)), 0)) }}</strong></div>
            </div>
            <p class="booking-total-unavailable" id="total-unavailable-note" @if($serviceTotal !== null) hidden @endif>
                The service price is unavailable. The total and remaining balance cannot be calculated.
            </p>
            <div class="booking-payment-actions">
                <button type="button" class="btn booking-secondary-button" id="edit-payment-button" @unless($hasSavedPayment) hidden @endunless>
                    <i class="bi bi-pencil" aria-hidden="true"></i>
                    Edit Payment
                </button>
                <button type="button" class="btn booking-secondary-button" id="cancel-payment-edit-button" hidden>
                    Cancel
                </button>
                <button type="submit" class="btn booking-primary-button" id="save-payment-button" @if($hasSavedPayment) hidden @endif>
                    <i class="bi bi-save2" aria-hidden="true"></i>
                    <span>{{ $hasSavedPayment ? 'Update Payment' : 'Save Payment' }}</span>
                </button>
            </div>
        </form>
    </section>

    @if($serviceBooking->status === 'pending')
        <form method="POST" action="{{ route('service-bookings.confirm', $serviceBooking) }}" class="booking-confirm-form" id="booking-confirm-form" @unless($hasSavedPayment) hidden @endunless>
            @csrf
            <input type="hidden" name="appointment_date" value="{{ $serviceBooking->appointment_date?->format('Y-m-d') }}" id="confirm-appointment-date">
            <button type="submit" class="btn booking-primary-button" id="confirm-booking-button" @disabled(! $serviceBooking->appointment_date)>
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                Confirm Service Booking
            </button>
            @error('payment')<div class="booking-inline-error">{{ $message }}</div>@enderror
        </form>
    @endif
</div>

<style>
    .booking-review-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .booking-back-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 12px;
        color: #2563eb;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
    }

    .booking-review-title {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .booking-review-subtitle {
        margin-top: 6px;
        color: #64748b;
        font-size: 0.9rem;
    }

    .booking-reference {
        display: inline-flex;
        padding: 0.25rem 0.45rem;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: 0.9rem;
        vertical-align: middle;
    }

    .booking-status-badge,
    .booking-payment-badge {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .booking-review-page {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }

    .booking-review-section {
        min-width: 0;
        padding: 22px 26px;
    }

    .booking-review-section + .booking-review-section {
        border-top: 1px solid #e2e8f0;
    }

    .booking-section-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .booking-section-icon {
        display: inline-flex;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: #eff6ff;
        color: #2563eb;
        font-size: 1.1rem;
    }

    .booking-section-heading h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 650;
    }

    .booking-section-heading p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.8125rem;
    }

    .booking-section-heading .booking-payment-badge {
        margin-left: auto;
    }

    .booking-information-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 20px 24px;
        margin: 0;
    }

    .booking-information-grid > div {
        min-width: 0;
    }

    .booking-information-grid dt {
        margin-bottom: 5px;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .booking-information-grid dd {
        margin: 0;
        color: #0f172a;
        font-size: 0.9rem;
        overflow-wrap: anywhere;
    }

    .booking-requirements {
        grid-column: 1 / -1;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
    }

    .booking-preferred-date {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
        min-width: 0;
        padding: 10px 14px;
        border-left: 3px solid #60a5fa;
        background: #f8fbff;
    }

    .booking-preferred-date span,
    .booking-field-label {
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .booking-preferred-date strong {
        color: #0f172a;
        font-size: 0.9rem;
    }

    .booking-action-form {
        display: block;
    }

    .booking-date-layout {
        display: grid;
        grid-template-columns: minmax(220px, 0.8fr) minmax(300px, 1.2fr);
        align-items: end;
        gap: 20px;
    }

    .booking-date-control {
        min-width: 0;
    }

    .booking-current-appointment {
        margin: 8px 0 0;
        color: #64748b;
        font-size: 0.8125rem;
    }

    .booking-current-appointment strong {
        color: #0f172a;
    }

    .booking-date-row {
        display: flex;
        gap: 10px;
    }

    .booking-input {
        min-height: 42px;
        border-color: #cbd5e1;
        border-radius: 7px;
        color: #0f172a;
    }

    .booking-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .booking-secondary-button,
    .booking-primary-button {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border-radius: 7px;
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .booking-secondary-button {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }

    .booking-secondary-button:hover {
        background: #f8fafc;
    }

    .booking-primary-button {
        border: 1px solid #2563eb;
        background: #2563eb;
        color: #fff;
    }

    .booking-primary-button:hover,
    .booking-primary-button:focus-visible {
        border-color: #1d4ed8;
        background: #1d4ed8;
        color: #fff;
    }

    .booking-confirm-form {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
        padding: 18px 26px 0;
        border-top: 1px solid #e2e8f0;
    }

    .booking-choice-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 0 0 18px;
        padding: 0;
        border: 0;
    }

    .booking-choice-group legend {
        width: 100%;
        margin-bottom: 10px;
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .booking-choice {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        gap: 8px;
        padding: 8px 13px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        color: #334155;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .booking-choice:has(input:checked) {
        border-color: #93c5fd;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .booking-choice input {
        accent-color: #2563eb;
    }

    .booking-payment-details {
        display: contents;
    }

    .booking-payment-type {
        margin-bottom: 0;
    }

    .booking-money-input {
        min-width: 0;
        align-self: start;
    }

    .booking-money-input .input-group {
        margin-top: 7px;
    }

    .booking-currency {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .booking-amount-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-column: 1 / -1;
        margin: 0;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
    }

    .booking-amount-summary > div {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 5px;
        padding: 13px 16px;
    }

    .booking-amount-summary > div + div {
        border-left: 1px solid #e2e8f0;
    }

    .booking-amount-summary span {
        color: #64748b;
        font-size: 0.75rem;
    }

    .booking-amount-summary strong {
        color: #0f172a;
        font-size: 1rem;
        font-variant-numeric: tabular-nums;
    }

    .booking-total-unavailable {
        color: #64748b;
        font-size: 0.8125rem;
        margin: 10px 0 0;
    }

    #booking-payment-form {
        display: grid;
        grid-template-columns: minmax(190px, 0.85fr) minmax(240px, 1fr) minmax(230px, 1fr) minmax(360px, 1.8fr);
        align-items: start;
        gap: 18px 24px;
    }

    #booking-payment-form > .booking-choice-group {
        margin: 0;
    }

    #booking-payment-form > .booking-payment-details {
        display: contents;
    }

    #booking-payment-form > .booking-amount-summary {
        grid-column: 1 / -1;
    }

    #booking-payment-form > .booking-total-unavailable {
        grid-column: 1 / -1;
    }

    .booking-payment-actions {
        display: flex;
        grid-column: 1 / -1;
        justify-content: flex-end;
        gap: 10px;
        padding-top: 2px;
    }

    .booking-choice input:disabled {
        cursor: not-allowed;
    }

    .booking-choice:has(input:disabled) {
        cursor: default;
        opacity: 0.8;
    }

    .booking-toast {
        position: fixed;
        z-index: 1090;
        top: 18px;
        right: 24px;
        display: flex;
        max-width: min(460px, calc(100vw - 32px));
        align-items: center;
        gap: 10px;
        padding: 13px 15px;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
        background: #f0fdf4;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        color: #166534;
        font-size: 0.875rem;
    }

    .booking-toast[data-type="error"] {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .booking-toast-icon {
        flex: 0 0 auto;
        font-size: 1rem;
    }

    .booking-toast-message {
        flex: 1;
    }

    .booking-toast-close {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 1.25rem;
        line-height: 1;
        opacity: 0.75;
    }

    .booking-toast[hidden],
    .booking-payment-details[hidden],
    [hidden] {
        display: none !important;
    }

    .booking-inline-error {
        color: #b91c1c;
        font-size: 0.8125rem;
    }

    @media (max-width: 1300px) {
        .booking-information-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        #booking-payment-form {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        #booking-payment-form > .booking-amount-summary {
            grid-column: 1 / -1;
        }
    }

    @media (max-width: 767.98px) {
        .booking-review-section {
            padding: 18px;
        }

        .booking-date-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        #booking-payment-form {
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
        }

        #booking-payment-form > .booking-amount-summary,
        .booking-payment-actions {
            grid-column: auto;
        }
    }

    @media (max-width: 575.98px) {
        .booking-review-header {
            align-items: flex-start;
        }

        .booking-review-title {
            font-size: 1.25rem;
        }

        .booking-information-grid {
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
        }

        .booking-date-row {
            align-items: stretch;
            flex-direction: column;
        }

        .booking-date-row .btn {
            width: 100%;
        }

        .booking-amount-summary {
            grid-template-columns: minmax(0, 1fr);
        }

        .booking-amount-summary > div + div {
            border-top: 1px solid #e2e8f0;
            border-left: 0;
        }

        .booking-payment-actions {
            flex-direction: column-reverse;
        }

        .booking-payment-actions .btn {
            width: 100%;
        }

        .booking-review-header {
            flex-direction: column;
            gap: 12px;
        }
    }
</style>

<script>
(() => {
    const toast = document.getElementById('booking-toast');
    let toastTimer;
    const showToast = (message, type = 'success') => {
        if (!toast || !message) return;
        toast.dataset.type = type;
        toast.querySelector('.booking-toast-message').textContent = message;
        toast.querySelector('.booking-toast-icon').className = `booking-toast-icon bi ${type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'}`;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 5000);
    };

    document.querySelectorAll('[data-booking-toast-message]').forEach((message) => {
        showToast(message.dataset.bookingToastMessage, message.dataset.bookingToastType);
    });
    toast?.querySelector('.booking-toast-close')?.addEventListener('click', () => {
        toast.hidden = true;
        clearTimeout(toastTimer);
    });

    const form = document.getElementById('booking-payment-form');
    if (!form) return;

    const total = form.dataset.total === '' ? null : Number(form.dataset.total);
    const details = document.getElementById('payment-details');
    const amountWrap = document.getElementById('amount-input-wrap');
    const amountInput = document.getElementById('amount-received');
    const amountError = document.getElementById('amount-error');
    const receivedDisplay = document.getElementById('booking-received');
    const balanceDisplay = document.getElementById('booking-balance');
    const totalUnavailableNote = document.getElementById('total-unavailable-note');
    const statusBadge = document.getElementById('payment-status-badge');
    const editButton = document.getElementById('edit-payment-button');
    const cancelButton = document.getElementById('cancel-payment-edit-button');
    const saveButton = document.getElementById('save-payment-button');
    const confirmForm = document.getElementById('booking-confirm-form');
    const confirmDate = document.getElementById('confirm-appointment-date');
    const appointmentDate = document.getElementById('appointment-date');
    const confirmButton = document.getElementById('confirm-booking-button');
    const formatMoney = (value) => `RWF ${new Intl.NumberFormat('en-RW', { maximumFractionDigits: 2 }).format(value)}`;

    let saved = form.dataset.saved === 'true';
    let editing = !saved;
    let saving = false;

    const selected = (name) => form.querySelector(`input[name="${name}"]:checked`)?.value ?? '';
    const statusText = (status) => ({
        paid: 'Paid',
        partially_paid: 'Partially Paid',
        not_paid: 'Not Paid',
    })[status];
    const receivedValue = () => {
        if (selected('has_paid') !== 'yes') return 0;
        if (selected('payment_type') === 'full' && total !== null) return total;
        const amount = Number(amountInput.value);
        return Number.isFinite(amount) && amount > 0 ? amount : 0;
    };
    const statusName = () => {
        if (selected('has_paid') !== 'yes') return 'not_paid';
        const received = receivedValue();
        if (received <= 0) return 'not_paid';
        return total !== null && received >= total ? 'paid' : 'partially_paid';
    };

    const render = () => {
        const hasPaid = selected('has_paid') === 'yes';
        const type = selected('payment_type');
        const needsAmount = hasPaid && (type === 'partial' || total === null);
        details.hidden = !hasPaid;
        amountWrap.hidden = !needsAmount;
        amountInput.required = needsAmount;
        amountInput.max = total === null ? '' : String(total);
        totalUnavailableNote.hidden = total !== null;

        const received = receivedValue();
        const status = statusName();
        const receivedKnown = status === 'not_paid'
            || (selected('payment_type') === 'full' && total !== null)
            || (amountInput.value !== '' && received > 0);
        receivedDisplay.textContent = receivedKnown ? (received > 0 ? formatMoney(received) : 'RWF 0') : 'Not recorded';
        balanceDisplay.textContent = total === null
            ? 'Unavailable'
            : (receivedKnown ? formatMoney(Math.max(0, total - received)) : 'Not recorded');
        statusBadge.textContent = statusText(status);
        statusBadge.className = `booking-payment-badge ${status}`;

        const savedStatus = form.dataset.initialStatus;
        const savedAmount = form.dataset.initialAmount === '' ? null : Number(form.dataset.initialAmount);
        const matchesSaved = saved
            && status === savedStatus
            && (status === 'not_paid'
                ? savedAmount === null
                : savedAmount !== null && Math.abs(received - savedAmount) < 0.005);
        if (confirmForm) confirmForm.hidden = !(matchesSaved && status !== 'not_paid');
        if (confirmButton && appointmentDate) confirmButton.disabled = appointmentDate.value === '';

        form.querySelectorAll('input').forEach((input) => {
            input.disabled = !editing || saving;
        });
        editButton.hidden = !saved || editing || saving;
        cancelButton.hidden = !saved || !editing || saving;
        saveButton.hidden = saved && !editing;
        saveButton.disabled = saving;
        saveButton.querySelector('span').textContent = saved ? 'Update Payment' : 'Save Payment';
    };

    const savePayment = async () => {
        if (saving || !editing) return;
        const hasPaid = selected('has_paid');
        const type = selected('payment_type');
        const received = receivedValue();
        amountError.textContent = '';

        if (hasPaid === 'yes' && (!type || ((type === 'partial' || total === null) && received <= 0))) {
            amountError.textContent = 'Enter an amount greater than zero.';
            showToast('Enter a valid amount received before saving.', 'error');
            return;
        }
        if (hasPaid === 'yes' && total !== null && type === 'partial' && received > total) {
            amountError.textContent = 'The amount received cannot exceed the booking total.';
            showToast('The amount received cannot exceed the booking total.', 'error');
            return;
        }

        const wasSaved = saved;
        const body = new FormData(form);
        body.set('_method', 'PATCH');
        saving = true;
        render();
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
                credentials: 'same-origin',
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(result.errors?.amount_received?.[0] ?? result.errors?.payment_type?.[0] ?? result.errors?.has_paid?.[0] ?? result.message ?? 'Payment information could not be saved.');
            }

            form.dataset.saved = 'true';
            form.dataset.initialStatus = result.payment_status;
            form.dataset.initialAmount = result.amount_received ?? '';
            saved = true;
            editing = false;
            amountInput.value = result.amount_received ?? '';
            showToast(wasSaved ? 'Payment information updated.' : 'Payment information saved.');
            render();
        } catch (error) {
            amountError.textContent = error.message;
            showToast(error.message || 'Payment information could not be saved.', 'error');
        } finally {
            saving = false;
            render();
        }
    };

    form.addEventListener('change', (event) => {
        if (event.target.name === 'has_paid') {
            if (selected('has_paid') === 'yes' && !selected('payment_type')) {
                form.querySelector('input[name="payment_type"][value="partial"]').checked = true;
            }
        }
        render();
    });

    amountInput.addEventListener('input', () => {
        render();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        savePayment();
    });

    editButton.addEventListener('click', () => {
        editing = true;
        render();
    });

    cancelButton.addEventListener('click', () => {
        const initialStatus = form.dataset.initialStatus;
        form.querySelector('input[name="has_paid"][value="yes"]').checked = initialStatus !== 'not_paid';
        form.querySelector('input[name="has_paid"][value="no"]').checked = initialStatus === 'not_paid';
        form.querySelectorAll('input[name="payment_type"]').forEach((input) => {
            input.checked = input.value === (initialStatus === 'paid' ? 'full' : 'partial')
                && initialStatus !== 'not_paid';
        });
        amountInput.value = form.dataset.initialAmount;
        amountError.textContent = '';
        editing = false;
        render();
    });

    appointmentDate?.addEventListener('change', () => {
        if (confirmDate) confirmDate.value = appointmentDate.value;
        if (confirmButton) confirmButton.disabled = appointmentDate.value === '';
    });

    if (confirmDate && appointmentDate) confirmDate.value = appointmentDate.value;
    render();
})();
</script>
@endsection
