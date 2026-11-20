@extends('front.layouts.admin')

@section('title', 'Completed Service Booking')
@section('page-title', 'Completed Service Booking')

@section('content')
@php
    $paymentLabel = match ($paymentStatus) {
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        default => 'Not Paid',
    };
    $replacePayment = $remainingBalance !== null && (float) $remainingBalance <= 0;
@endphp

<div class="completed-booking-header">
    <div>
        <h1>Completed booking <span class="completed-booking-reference">{{ $serviceBooking->reference }}</span></h1>
        <p>Booking and completion details are read-only</p>
    </div>
    <span class="completed-status-badge">Completed</span>
</div>

<div class="completed-booking-toast" id="completed-booking-toast" role="status" aria-live="polite" hidden>
    <span class="completed-booking-toast-icon bi" aria-hidden="true"></span>
    <span class="completed-booking-toast-message"></span>
    <button type="button" class="completed-booking-toast-close" aria-label="Dismiss notification">&times;</button>
</div>
@if(session('success'))
    <div data-completed-toast-message="{{ session('success') }}" data-completed-toast-type="success" hidden></div>
@endif
@if($errors->any())
    <div data-completed-toast-message="{{ $errors->first() }}" data-completed-toast-type="error" hidden></div>
@endif

<div class="completed-booking-page">
    <section class="completed-booking-section" aria-labelledby="completed-booking-information">
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-booking-information">Booking information</h2>
                <p>Customer, service, and original request details</p>
            </div>
        </div>
        <dl class="completed-information-grid">
            <div><dt>Booking reference</dt><dd>{{ $serviceBooking->reference }}</dd></div>
            <div><dt>Customer</dt><dd>{{ $serviceBooking->customer_name }}</dd></div>
            <div><dt>Phone</dt><dd>{{ $serviceBooking->customer_phone }}</dd></div>
            <div><dt>Email</dt><dd>{{ $serviceBooking->customer_email ?: 'Not provided' }}</dd></div>
            <div><dt>Service</dt><dd>{{ $serviceBooking->service?->title ?? 'Service unavailable' }}</dd></div>
            <div><dt>Preferred appointment date</dt><dd>{{ $serviceBooking->preferred_date->format('M j, Y') }}</dd></div>
            <div class="completed-requirements">
                <dt>Requirements</dt>
                <dd>{{ $serviceBooking->requirements ?: 'No additional requirements provided.' }}</dd>
            </div>
        </dl>
    </section>

    <section class="completed-booking-section" aria-labelledby="completed-appointment-information">
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-appointment-information">Appointment information</h2>
                <p>Confirmed appointment and completion details</p>
            </div>
        </div>
        <dl class="completed-appointment-grid">
            <div>
                <dt>Booking status</dt>
                <dd><span class="completed-status-badge">Completed</span></dd>
            </div>
            <div>
                <dt>Confirmed appointment date</dt>
                <dd>{{ $serviceBooking->appointment_date?->format('l, M j, Y') ?? 'Not recorded' }}</dd>
            </div>
            <div>
                <dt>Appointment time</dt>
                <dd>{{ $serviceBooking->appointment_time ? ucfirst($serviceBooking->appointment_time) : 'Not recorded' }}</dd>
            </div>
            <div>
                <dt>Confirmation details</dt>
                <dd>
                    {{ $serviceBooking->appointment_date
                        ? 'Confirmed with the appointment date shown above.'
                        : 'Confirmation timestamp is not recorded.' }}
                </dd>
            </div>
            <div>
                <dt>Completed on</dt>
                <dd>{{ $serviceBooking->completed_at?->format('M j, Y \a\t g:i A') ?? 'Completion date and time not recorded' }}</dd>
            </div>
        </dl>
    </section>

    <section
        class="completed-booking-section"
        id="completed-payment-summary-section"
        aria-labelledby="completed-payment-summary"
        data-total="{{ $serviceTotal ?? '' }}"
        data-received="{{ $amountReceived !== null ? number_format((float) $amountReceived, 2, '.', '') : '0.00' }}"
    >
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-payment-summary">Payment summary</h2>
                <p>Saved payment totals and final payment status</p>
            </div>
            <span class="completed-payment-badge {{ $paymentStatus }}">{{ $paymentLabel }}</span>
        </div>
        <dl class="completed-payment-grid">
            <div>
                <dt>Total booking amount</dt>
                <dd>{{ $serviceTotal !== null ? 'RWF '.number_format((float) $serviceTotal, 0) : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Total amount received</dt>
                <dd>{{ $amountReceived !== null ? 'RWF '.number_format((float) $amountReceived, 0) : 'Not recorded' }}</dd>
            </div>
            <div>
                <dt>Remaining balance</dt>
                <dd>{{ $remainingBalance !== null ? 'RWF '.number_format((float) $remainingBalance, 0) : 'Unavailable' }}</dd>
            </div>
            <div>
                <dt>Final payment status</dt>
                <dd><span class="completed-payment-badge {{ $paymentStatus }}">{{ $paymentLabel }}</span></dd>
            </div>
        </dl>
        @if($serviceTotal === null)
            <p class="completed-information-note">The service price is unavailable, so a total or remaining balance cannot be calculated.</p>
        @endif
        <div class="completed-payment-actions">
            <button
                type="button"
                class="btn completed-edit-payment-button"
                id="edit-completed-payment"
                @if($errors->has('amount_received')) hidden @endif
            >
                <i class="bi bi-pencil" aria-hidden="true"></i> Edit Payment
            </button>
        </div>
        <form
            id="completed-payment-form"
            action="{{ route('service-bookings.additional-payment', $serviceBooking) }}"
            method="POST"
            data-mode="{{ $replacePayment ? 'replace' : 'additional' }}"
            @unless($errors->has('amount_received')) hidden @endunless
        >
            @csrf
            @if($replacePayment) <input type="hidden" name="payment_mode" value="replace"> @endif
            <div class="completed-payment-entry">
                <div>
                    <label class="completed-field-label" for="completed-additional-amount">
                        {{ $replacePayment ? 'Total amount received' : 'Additional amount received' }}
                    </label>
                    <div class="input-group">
                        <span class="input-group-text completed-currency">RWF</span>
                        <input
                            id="completed-additional-amount"
                            name="amount_received"
                            type="number"
                            class="form-control completed-payment-input"
                            min="{{ $replacePayment ? '0' : '0.01' }}"
                            step="0.01"
                            @if($replacePayment) max="{{ $serviceTotal }}" @elseif($remainingBalance !== null) max="{{ $remainingBalance }}" @endif
                            inputmode="decimal"
                            value="{{ old('amount_received', $replacePayment ? $amountReceived : null) }}"
                            aria-describedby="completed-payment-help completed-payment-error"
                            required
                        >
                    </div>
                    <small class="completed-payment-help" id="completed-payment-help">
                        {{ $replacePayment ? 'Update the total amount received for this completed booking.' : 'This amount will be added to the total already received.' }}
                    </small>
                    @error('amount_received')
                        <small class="completed-payment-error" id="completed-payment-error">{{ $message }}</small>
                    @enderror
                </div>
                <div class="completed-payment-entry-actions">
                    <button type="button" class="btn completed-cancel-payment-button" id="cancel-completed-payment">Cancel</button>
                    <button type="submit" class="btn completed-save-payment-button" id="save-completed-payment">
                        <i class="bi bi-save2" aria-hidden="true"></i> Save Payment Update
                    </button>
                </div>
            </div>
            <p class="completed-payment-preview" id="completed-payment-preview" aria-live="polite"></p>
        </form>
    </section>

    <section class="completed-booking-section" aria-labelledby="completed-payment-history">
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-payment-history">Payment history</h2>
                <p>Transaction entries and recording dates</p>
            </div>
        </div>
        <p class="completed-history-note">
            Individual payment transactions and their dates are not stored for this booking. The saved aggregate amount received is shown in the payment summary.
        </p>
    </section>

    <section class="completed-booking-section" aria-labelledby="completed-notes-heading">
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-notes-heading">Completion information</h2>
                <p>Notes recorded when the service was completed</p>
            </div>
        </div>
        <p class="completed-history-note">No completion notes are recorded for this booking.</p>
    </section>
</div>

<div class="completed-booking-footer">
    <a class="btn completed-back-button" href="{{ route('service-bookings.index', ['tab' => 'completed']) }}">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Service Bookings
    </a>
</div>

<style>
    .completed-booking-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 24px;
    }

    .completed-booking-header h1 {
        margin: 0;
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .completed-booking-header p {
        margin: 6px 0 0;
        color: #64748b;
        font-size: .875rem;
    }

    .completed-booking-reference {
        display: inline-flex;
        padding: 4px 8px;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        font-size: .9rem;
        vertical-align: middle;
    }

    .completed-status-badge,
    .completed-payment-badge {
        display: inline-flex;
        width: fit-content;
        align-items: center;
        padding: 6px 10px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #f8fafc;
        color: #475569;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .completed-status-badge {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .completed-payment-badge.paid {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .completed-payment-badge.partially_paid {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .completed-booking-page {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
    }

    .completed-booking-section {
        min-width: 0;
        padding: 22px 26px;
    }

    .completed-booking-section + .completed-booking-section {
        border-top: 1px solid #e2e8f0;
    }

    .completed-section-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .completed-section-icon {
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

    .completed-section-heading h2 {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 650;
    }

    .completed-section-heading p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: .8125rem;
    }

    .completed-section-heading > .completed-payment-badge {
        margin-left: auto;
    }

    .completed-information-grid,
    .completed-appointment-grid,
    .completed-payment-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px 24px;
        margin: 0;
    }

    .completed-information-grid > div,
    .completed-appointment-grid > div,
    .completed-payment-grid > div {
        min-width: 0;
    }

    .completed-information-grid dt,
    .completed-appointment-grid dt,
    .completed-payment-grid dt {
        margin-bottom: 5px;
        color: #64748b;
        font-size: .75rem;
        font-weight: 600;
    }

    .completed-information-grid dd,
    .completed-appointment-grid dd,
    .completed-payment-grid dd {
        margin: 0;
        color: #0f172a;
        font-size: .9rem;
        overflow-wrap: anywhere;
    }

    .completed-requirements {
        grid-column: 1 / -1;
        padding-top: 14px;
        border-top: 1px solid #f1f5f9;
    }

    .completed-payment-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .completed-payment-grid dd {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }

    .completed-history-note,
    .completed-information-note {
        margin: 0;
        color: #64748b;
        font-size: .875rem;
        line-height: 1.6;
    }

    .completed-information-note {
        margin-top: 12px;
    }

    .completed-payment-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 16px;
    }

    .completed-payment-entry {
        display: grid;
        grid-template-columns: minmax(240px, .7fr) minmax(260px, 1fr);
        align-items: end;
        gap: 18px 24px;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .completed-field-label {
        display: block;
        margin-bottom: 7px;
        color: #475569;
        font-size: .8125rem;
        font-weight: 600;
    }

    .completed-payment-input {
        min-height: 42px;
        border-color: #cbd5e1;
        border-radius: 7px;
        color: #0f172a;
    }

    .completed-currency {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #475569;
        font-size: .8125rem;
        font-weight: 600;
    }

    .completed-payment-help,
    .completed-payment-error,
    .completed-payment-preview {
        display: block;
        margin: 7px 0 0;
        color: #64748b;
        font-size: .8125rem;
    }

    .completed-payment-error { color: #b91c1c; }
    .completed-payment-preview:empty { display: none; }

    .completed-payment-entry-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .completed-edit-payment-button,
    .completed-cancel-payment-button,
    .completed-save-payment-button {
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

    .completed-edit-payment-button,
    .completed-cancel-payment-button {
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
    }

    .completed-save-payment-button {
        border: 1px solid #2563eb;
        background: #2563eb;
        color: #fff;
    }

    .completed-save-payment-button:hover,
    .completed-save-payment-button:focus-visible {
        border-color: #1d4ed8;
        background: #1d4ed8;
        color: #fff;
    }

    .completed-booking-toast {
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

    .completed-booking-toast[data-type="error"] {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .completed-booking-toast-message { flex: 1; }

    .completed-booking-toast-close {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 1.25rem;
        line-height: 1;
    }

    .completed-booking-footer {
        display: flex;
        justify-content: flex-start;
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }

    .completed-back-button {
        display: inline-flex;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 9px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        background: #fff;
        color: #334155;
        font-size: .875rem;
        font-weight: 600;
        text-decoration: none;
    }

    .completed-back-button:hover {
        background: #f8fafc;
        color: #334155;
    }

    @media (max-width: 1100px) {
        .completed-information-grid,
        .completed-appointment-grid,
        .completed-payment-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .completed-booking-header {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .completed-booking-header h1 {
            font-size: 1.25rem;
        }

        .completed-booking-section {
            padding: 18px;
        }

        .completed-information-grid,
        .completed-appointment-grid,
        .completed-payment-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .completed-payment-entry {
            grid-template-columns: minmax(0, 1fr);
        }

        .completed-payment-entry-actions {
            flex-direction: column;
        }

        .completed-payment-entry-actions .btn { width: 100%; }

        .completed-requirements {
            grid-column: auto;
        }

        .completed-section-heading {
            align-items: flex-start;
        }

        .completed-section-heading > .completed-payment-badge {
            margin-left: auto;
        }

        .completed-back-button {
            width: 100%;
        }
    }
</style>

<script>
(() => {
    const toast = document.getElementById('completed-booking-toast');
    let toastTimer;
    const showToast = (message, type = 'success') => {
        if (!toast || !message) return;
        toast.dataset.type = type;
        toast.querySelector('.completed-booking-toast-message').textContent = message;
        toast.querySelector('.completed-booking-toast-icon').className = `completed-booking-toast-icon bi ${type === 'error' ? 'bi-exclamation-circle' : 'bi-check-circle'}`;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 5000);
    };

    document.querySelectorAll('[data-completed-toast-message]').forEach((item) => {
        showToast(item.dataset.completedToastMessage, item.dataset.completedToastType);
    });
    toast?.querySelector('.completed-booking-toast-close')?.addEventListener('click', () => {
        toast.hidden = true;
        clearTimeout(toastTimer);
    });

    const summary = document.getElementById('completed-payment-summary-section');
    const form = document.getElementById('completed-payment-form');
    const edit = document.getElementById('edit-completed-payment');
    const cancel = document.getElementById('cancel-completed-payment');
    const amountInput = document.getElementById('completed-additional-amount');
    const preview = document.getElementById('completed-payment-preview');
    const saveButton = document.getElementById('save-completed-payment');
    if (!summary || !form || !edit || !amountInput) return;

    const total = summary.dataset.total === '' ? null : Number(summary.dataset.total);
    const currentReceived = Number(summary.dataset.received);
    const mode = form.dataset.mode;
    const formatMoney = (value) => `RWF ${new Intl.NumberFormat('en-RW', { maximumFractionDigits: 2 }).format(value)}`;
    const updatePreview = () => {
        const enteredAmount = Number(amountInput.value);
        if (!Number.isFinite(enteredAmount) || enteredAmount < 0 || (mode === 'additional' && enteredAmount === 0)) {
            preview.textContent = '';
            return;
        }
        const received = mode === 'replace' ? enteredAmount : currentReceived + enteredAmount;
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
    if (amountInput.value) updatePreview();
    form.addEventListener('submit', () => {
        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="bi bi-hourglass-split" aria-hidden="true"></i> Saving...';
    });
})();
</script>
@endsection
