@extends('front.layouts.admin')

@section('title', 'Manage Confirmed Booking')
@section('page-title', 'Manage Confirmed Booking')

@section('content')
@php
    $paymentLabel = match ($serviceBooking->payment_status) {
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        default => 'Not Paid',
    };
@endphp

<div class="confirmed-booking-header">
    <div>
        <a class="confirmed-booking-back" href="{{ route('service-bookings.index', ['tab' => 'confirmed']) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Confirmed bookings
        </a>
        <h1>Booking <span class="confirmed-booking-reference">{{ $serviceBooking->reference }}</span></h1>
    </div>
    <span class="confirmed-booking-status">{{ ucfirst($serviceBooking->status) }}</span>
</div>

<div class="confirmed-booking-toast" id="confirmed-booking-toast" role="status" aria-live="polite" hidden>
    <span class="confirmed-booking-toast-icon bi" aria-hidden="true"></span>
    <span class="confirmed-booking-toast-message"></span>
    <button type="button" class="confirmed-booking-toast-close" aria-label="Dismiss notification">&times;</button>
</div>
@if(session('success'))
    <div data-confirmed-toast-message="{{ session('success') }}" data-confirmed-toast-type="success" hidden></div>
@endif
@if($errors->any())
    <div data-confirmed-toast-message="{{ $errors->first() }}" data-confirmed-toast-type="error" hidden></div>
@endif

<div class="confirmed-booking-page">
    <section class="confirmed-booking-section" aria-labelledby="confirmed-booking-information">
        <div class="confirmed-section-heading">
            <span class="confirmed-section-icon"><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
            <div>
                <h2 id="confirmed-booking-information">Booking information</h2>
                <p>Customer, service, and requirements</p>
            </div>
        </div>
        <dl class="confirmed-information-grid">
            <div><dt>Customer</dt><dd>{{ $serviceBooking->customer_name }}</dd></div>
            <div><dt>Service</dt><dd>{{ $serviceBooking->service?->title ?? 'Service unavailable' }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $serviceBooking->customer_phone }}</dd></div>
            <div><dt>Email</dt><dd>{{ $serviceBooking->customer_email ?: 'Not provided' }}</dd></div>
            <div><dt>Preferred date</dt><dd>{{ $serviceBooking->preferred_date->format('M j, Y') }}</dd></div>
            <div class="confirmed-requirements"><dt>Requirements</dt><dd>{{ $serviceBooking->requirements ?: 'No additional requirements provided.' }}</dd></div>
        </dl>
    </section>

    <section class="confirmed-booking-section" aria-labelledby="confirmed-appointment-heading">
        <div class="confirmed-section-heading">
            <span class="confirmed-section-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
            <div>
                <h2 id="confirmed-appointment-heading">Confirmed appointment</h2>
                <p>Agreed appointment details</p>
            </div>
        </div>
        <div class="confirmed-appointment-details">
            <span>Appointment date</span>
            <strong>{{ $serviceBooking->appointment_date?->format('l, M j, Y') ?? 'Not scheduled' }}</strong>
            @if($serviceBooking->appointment_time)
                <span>Appointment time</span>
                <strong>{{ ucfirst($serviceBooking->appointment_time) }}</strong>
            @endif
        </div>
    </section>

    <section class="confirmed-booking-section" aria-labelledby="confirmed-payment-heading">
        <div class="confirmed-section-heading">
            <span class="confirmed-section-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <div>
                <h2 id="confirmed-payment-heading">Payment information</h2>
                <p>Record additional payments on this booking</p>
            </div>
            <span class="confirmed-payment-badge {{ $serviceBooking->payment_status }}" id="confirmed-payment-status">{{ $paymentLabel }}</span>
        </div>

        <div
            class="confirmed-payment-summary"
            id="confirmed-payment-summary"
            data-total="{{ $serviceTotal ?? '' }}"
            data-received="{{ $amountReceived }}"
        >
            <div><span>Total booking amount</span><strong id="confirmed-total">{{ $serviceTotal !== null ? 'RWF '.number_format((float) $serviceTotal, 0) : 'Unavailable' }}</strong></div>
            <div><span>Amount received</span><strong id="confirmed-received">{{ 'RWF '.number_format((float) $amountReceived, 0) }}</strong></div>
            <div><span>Remaining balance</span><strong id="confirmed-balance">{{ $remainingBalance !== null ? 'RWF '.number_format((float) $remainingBalance, 0) : 'Unavailable' }}</strong></div>
        </div>
        @if($serviceTotal === null)
            <p class="confirmed-payment-note">The service price is unavailable, so the total and remaining balance cannot be calculated.</p>
        @endif

        <div class="confirmed-payment-actions">
            <button type="button" class="btn confirmed-secondary-button" id="edit-confirmed-payment">
                <i class="bi bi-pencil" aria-hidden="true"></i> Edit Payment
            </button>
        </div>
        <form
            id="confirmed-payment-form"
            action="{{ route('service-bookings.additional-payment', $serviceBooking) }}"
            method="POST"
            hidden
        >
            @csrf
            <div class="confirmed-payment-entry">
                <div>
                    <label class="confirmed-field-label" for="additional-amount">Additional amount received</label>
                    <div class="input-group">
                        <span class="input-group-text confirmed-currency">RWF</span>
                        <input
                            id="additional-amount"
                            name="amount_received"
                            type="number"
                            class="form-control confirmed-input"
                            min="0.01"
                            step="0.01"
                            @if($remainingBalance !== null) max="{{ $remainingBalance }}" @endif
                            inputmode="decimal"
                            required
                        >
                    </div>
                    <small class="confirmed-input-help">This amount will be added to the payment already recorded.</small>
                </div>
                <div class="confirmed-entry-actions">
                    <button type="button" class="btn confirmed-secondary-button" id="cancel-confirmed-payment">Cancel</button>
                    <button type="submit" class="btn confirmed-primary-button" id="save-confirmed-payment">
                        <i class="bi bi-save2" aria-hidden="true"></i> Save Payment Update
                    </button>
                </div>
            </div>
            <p class="confirmed-payment-note" id="confirmed-payment-preview" aria-live="polite"></p>
        </form>
    </section>
</div>

<form method="POST" action="{{ route('service-bookings.complete', $serviceBooking) }}" class="confirmed-complete-form">
    @csrf
    <button type="submit" class="btn confirmed-primary-button">
        <i class="bi bi-check2-circle" aria-hidden="true"></i> Mark as Completed
    </button>
</form>

<style>
    .confirmed-booking-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .confirmed-booking-back {
        display: inline-flex;
        gap: 7px;
        align-items: center;
        margin-bottom: 12px;
        color: #2563eb;
        font-size: .875rem;
        font-weight: 600;
        text-decoration: none;
    }

    .confirmed-booking-header h1 {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .confirmed-booking-reference {
        display: inline-flex;
        padding: 4px 8px;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: .9rem;
        vertical-align: middle;
    }

    .confirmed-booking-status,
    .confirmed-payment-badge {
        display: inline-flex;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .confirmed-payment-badge.paid {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .confirmed-payment-badge.partially_paid {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .confirmed-booking-page {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }

    .confirmed-booking-section {
        min-width: 0;
        padding: 22px 26px;
    }

    .confirmed-booking-section + .confirmed-booking-section {
        border-top: 1px solid #e2e8f0;
    }

    .confirmed-section-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .confirmed-section-icon {
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

    .confirmed-section-heading h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 650;
    }

    .confirmed-section-heading p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: .8125rem;
    }

    .confirmed-payment-badge {
        margin-left: auto;
    }

    .confirmed-information-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 20px 24px;
        margin: 0;
    }

    .confirmed-information-grid > div {
        min-width: 0;
    }

    .confirmed-information-grid dt,
    .confirmed-appointment-details > span {
        margin-bottom: 5px;
        color: #64748b;
        font-size: .75rem;
        font-weight: 600;
    }

    .confirmed-information-grid dd {
        margin: 0;
        color: #0f172a;
        font-size: .9rem;
        overflow-wrap: anywhere;
    }

    .confirmed-requirements {
        grid-column: 1 / -1;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
    }

    .confirmed-appointment-details {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
        padding: 12px 14px;
        border-left: 3px solid #60a5fa;
        background: #f8fbff;
    }

    .confirmed-appointment-details strong {
        margin-bottom: 8px;
        color: #0f172a;
        font-size: .95rem;
    }

    .confirmed-appointment-details strong:last-child {
        margin-bottom: 0;
    }

    .confirmed-payment-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
    }

    .confirmed-payment-summary > div {
        display: flex;
        min-width: 0;
        flex-direction: column;
        gap: 5px;
        padding: 13px 16px;
    }

    .confirmed-payment-summary > div + div {
        border-left: 1px solid #e2e8f0;
    }

    .confirmed-payment-summary span {
        color: #64748b;
        font-size: .75rem;
    }

    .confirmed-payment-summary strong {
        color: #0f172a;
        font-size: 1rem;
        font-variant-numeric: tabular-nums;
    }

    .confirmed-payment-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .confirmed-payment-entry {
        display: grid;
        grid-template-columns: minmax(240px, .7fr) minmax(260px, 1fr);
        align-items: end;
        gap: 18px 24px;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .confirmed-field-label {
        display: block;
        margin-bottom: 7px;
        color: #475569;
        font-size: .8125rem;
        font-weight: 600;
    }

    .confirmed-input {
        min-height: 42px;
        border-color: #cbd5e1;
        border-radius: 7px;
        color: #0f172a;
    }

    .confirmed-currency {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #475569;
        font-size: .8125rem;
        font-weight: 600;
    }

    .confirmed-input-help,
    .confirmed-payment-note {
        display: block;
        margin: 7px 0 0;
        color: #64748b;
        font-size: .8125rem;
    }

    .confirmed-entry-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .confirmed-secondary-button,
    .confirmed-primary-button {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border-radius: 7px;
        font-size: .875rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .confirmed-secondary-button {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }

    .confirmed-primary-button {
        border: 1px solid #2563eb;
        background: #2563eb;
        color: #fff;
    }

    .confirmed-primary-button:hover,
    .confirmed-primary-button:focus-visible {
        border-color: #1d4ed8;
        background: #1d4ed8;
        color: #fff;
    }

    .confirmed-complete-form {
        display: flex;
        justify-content: flex-end;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .confirmed-booking-toast {
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
        box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
        color: #166534;
        font-size: .875rem;
    }

    .confirmed-booking-toast[data-type="error"] {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .confirmed-booking-toast-message {
        flex: 1;
    }

    .confirmed-booking-toast-close {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 1.25rem;
        line-height: 1;
    }

    [hidden] {
        display: none !important;
    }

    @media (max-width: 1300px) {
        .confirmed-information-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .confirmed-booking-section {
            padding: 18px;
        }

        .confirmed-information-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .confirmed-payment-entry {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 575.98px) {
        .confirmed-booking-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .confirmed-booking-header h1 {
            font-size: 1.25rem;
        }

        .confirmed-information-grid,
        .confirmed-payment-summary {
            grid-template-columns: minmax(0, 1fr);
        }

        .confirmed-requirements {
            grid-column: auto;
        }

        .confirmed-payment-summary > div + div {
            border-top: 1px solid #e2e8f0;
            border-left: 0;
        }

        .confirmed-entry-actions,
        .confirmed-complete-form {
            flex-direction: column;
        }

        .confirmed-entry-actions .btn,
        .confirmed-complete-form .btn {
            width: 100%;
        }
    }
</style>

<script>
(() => {
    const toast = document.getElementById('confirmed-booking-toast');
    let toastTimer;
    const showToast = (message, type = 'success') => {
        if (!toast || !message) return;
        toast.dataset.type = type;
        toast.querySelector('.confirmed-booking-toast-message').textContent = message;
        toast.querySelector('.confirmed-booking-toast-icon').className = `confirmed-booking-toast-icon bi ${type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'}`;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 5000);
    };

    document.querySelectorAll('[data-confirmed-toast-message]').forEach((item) => {
        showToast(item.dataset.confirmedToastMessage, item.dataset.confirmedToastType);
    });
    toast?.querySelector('.confirmed-booking-toast-close')?.addEventListener('click', () => {
        toast.hidden = true;
        clearTimeout(toastTimer);
    });

    const summary = document.getElementById('confirmed-payment-summary');
    const form = document.getElementById('confirmed-payment-form');
    const edit = document.getElementById('edit-confirmed-payment');
    const cancel = document.getElementById('cancel-confirmed-payment');
    const amountInput = document.getElementById('additional-amount');
    const preview = document.getElementById('confirmed-payment-preview');
    const receivedDisplay = document.getElementById('confirmed-received');
    const balanceDisplay = document.getElementById('confirmed-balance');
    const saveButton = document.getElementById('save-confirmed-payment');
    if (!summary || !form || !edit || !amountInput) return;

    const total = summary.dataset.total === '' ? null : Number(summary.dataset.total);
    const currentReceived = Number(summary.dataset.received);
    const formatMoney = (value) => `RWF ${new Intl.NumberFormat('en-RW', { maximumFractionDigits: 2 }).format(value)}`;
    const updatePreview = () => {
        const additional = Number(amountInput.value);
        if (!Number.isFinite(additional) || additional <= 0) {
            preview.textContent = '';
            return;
        }
        const received = currentReceived + additional;
        const remaining = total === null ? null : Math.max(0, total - received);
        const status = total !== null
            ? (received >= total ? 'Paid' : 'Partially Paid')
            : 'Total unavailable; payment status cannot be calculated';
        preview.textContent = `After this update: ${formatMoney(received)} received${remaining === null ? '' : `, ${formatMoney(remaining)} remaining`}. ${status}.`;
    };

    edit.addEventListener('click', () => {
        form.hidden = false;
        edit.hidden = true;
        amountInput.focus();
    });
    cancel?.addEventListener('click', () => {
        form.hidden = true;
        edit.hidden = false;
        amountInput.value = '';
        preview.textContent = '';
    });
    amountInput.addEventListener('input', updatePreview);
    form.addEventListener('submit', (event) => {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Saving...';
    });

    receivedDisplay.textContent = formatMoney(currentReceived);
    if (total !== null) {
        balanceDisplay.textContent = formatMoney(Math.max(0, total - currentReceived));
    }
})();
</script>
@endsection
