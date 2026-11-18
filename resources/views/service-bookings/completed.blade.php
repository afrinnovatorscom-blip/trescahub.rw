@extends('front.layouts.admin')

@section('title', 'Completed Service Booking')
@section('page-title', 'Completed Service Booking')

@section('content')
@php
    $paymentLabel = match ($serviceBooking->payment_status) {
        'paid' => 'Paid',
        'partially_paid' => 'Partially Paid',
        default => 'Not Paid',
    };
@endphp

<div class="completed-booking-header">
    <div>
        <h1>Completed booking <span class="completed-booking-reference">{{ $serviceBooking->reference }}</span></h1>
        <p>Read-only booking history</p>
    </div>
    <span class="completed-status-badge">Completed</span>
</div>

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

    <section class="completed-booking-section" aria-labelledby="completed-payment-summary">
        <div class="completed-section-heading">
            <span class="completed-section-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <div>
                <h2 id="completed-payment-summary">Payment summary</h2>
                <p>Saved payment totals and final payment status</p>
            </div>
            <span class="completed-payment-badge {{ $serviceBooking->payment_status }}">{{ $paymentLabel }}</span>
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
                <dd><span class="completed-payment-badge {{ $serviceBooking->payment_status }}">{{ $paymentLabel }}</span></dd>
            </div>
        </dl>
        @if($serviceTotal === null)
            <p class="completed-information-note">The service price is unavailable, so a total or remaining balance cannot be calculated.</p>
        @endif
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
@endsection
