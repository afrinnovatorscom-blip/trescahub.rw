@extends('front.layouts.admin')

@section('title', 'Service Bookings')
@section('page-title', 'Service Bookings')

@section('content')
<div class="mb-4">
    <h1 class="fw-bold mb-1 fs-4">Service Bookings</h1>
    <p class="text-muted mb-0 small">Manage customer bookings for services.</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="status">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@if(session('completed_success'))
    <div class="booking-completion-toast" id="booking-completion-toast" role="status" aria-live="polite">
        <i class="bi bi-check-circle" aria-hidden="true"></i>
        <span>{{ session('completed_success') }}</span>
        <button type="button" aria-label="Dismiss notification">&times;</button>
    </div>
@endif

<nav class="booking-tabs mb-0" aria-label="Service booking status">
    @foreach(['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed'] as $tab => $label)
        <a href="{{ route('service-bookings.index', ['tab' => $tab]) }}"
           class="booking-tab {{ $activeTab === $tab ? 'active' : '' }}"
           @if($activeTab === $tab) aria-current="page" @endif>
            {{ $label }}
        </a>
    @endforeach
</nav>

<section class="booking-table-surface">
    <div class="table-responsive booking-table-responsive">
        <table class="table booking-table align-middle mb-0">
            <colgroup>
                <col class="booking-column-reference">
                <col class="booking-column-customer">
                <col class="booking-column-service">
                <col class="booking-column-date">
                <col class="booking-column-contact">
                <col class="booking-column-payment">
                <col class="booking-column-action">
            </colgroup>
            <thead>
                <tr>
                    <th scope="col">Booking Ref</th>
                    <th scope="col">Customer</th>
                    <th scope="col">Service</th>
                    <th scope="col">Preferred Date</th>
                    <th scope="col">Contact</th>
                    <th scope="col">Payment Status</th>
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                    @php
                        $paymentLabel = match ($booking->payment_status) {
                            'paid' => 'Paid',
                            'partially_paid' => 'Partially Paid',
                            default => 'Not Paid',
                        };
                        $paymentClass = match ($booking->payment_status) {
                            'paid' => 'paid',
                            'partially_paid' => 'partial',
                            default => 'not-paid',
                        };
                    @endphp
                    <tr>
                        <td><span class="booking-reference">{{ $booking->reference }}</span></td>
                        <td>
                            <strong>{{ $booking->customer_name }}</strong>
                            @if($booking->customer)<span class="booking-account-label">Account customer</span>@endif
                        </td>
                        <td>{{ $booking->service?->title ?? 'Service unavailable' }}</td>
                        <td>{{ $booking->preferred_date->format('M j, Y') }}</td>
                        <td>
                            <span>{{ $booking->customer_phone }}</span>
                            @if($booking->customer_email)<span class="booking-contact-email">{{ $booking->customer_email }}</span>@endif
                        </td>
                        <td><span class="booking-payment-status {{ $paymentClass }}">{{ $paymentLabel }}</span></td>
                        <td>
                            @if($activeTab === 'completed')
                                <a class="booking-view-action" href="{{ route('service-bookings.completed.show', $booking) }}">View</a>
                            @elseif($activeTab === 'confirmed')
                                <a class="booking-view-action" href="{{ route('service-bookings.confirmed.show', $booking) }}">Open</a>
                            @else
                                <a class="booking-view-action" href="{{ route('service-bookings.show', $booking) }}">View Booking</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="booking-empty">No {{ $activeTab }} bookings found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if($bookings->hasPages())
    <div class="mt-3 d-flex justify-content-end">{{ $bookings->links() }}</div>
@endif

<style>
    .booking-completion-toast {
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

    .booking-completion-toast span {
        flex: 1;
    }

    .booking-completion-toast button {
        border: 0;
        background: transparent;
        color: inherit;
        font-size: 1.25rem;
        line-height: 1;
    }

    .booking-tabs {
        display: flex;
        gap: 18px;
        border-bottom: 1px solid #e2e8f0;
    }

    .booking-tab {
        padding: 12px 4px;
        border-bottom: 3px solid transparent;
        color: #64748b;
        font-weight: 600;
        text-decoration: none;
    }

    .booking-tab:hover {
        color: #2563eb;
    }

    .booking-tab.active {
        border-bottom-color: #2563eb;
        color: #2563eb;
    }

    .booking-table-surface {
        border: 1px solid #e2e8f0;
        border-top: 0;
        border-radius: 0 0 10px 10px;
        background: #fff;
    }

    .booking-table-responsive {
        width: 100%;
        overflow-x: auto;
        overscroll-behavior-x: contain;
        border-radius: 0 0 10px 10px;
    }

    .booking-table {
        min-width: 1080px;
        table-layout: fixed;
        width: 100%;
    }

    .booking-column-reference {
        width: 10%;
    }

    .booking-column-customer {
        width: 15%;
    }

    .booking-column-service {
        width: 17%;
    }

    .booking-column-date {
        width: 13%;
    }

    .booking-column-contact {
        width: 17%;
    }

    .booking-column-payment {
        width: 13%;
    }

    .booking-column-action {
        width: 15%;
    }

    .booking-table thead th {
        padding: 12px 10px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .booking-table tbody td {
        padding: 12px 10px;
        border-color: #f1f5f9;
        color: #475569;
        font-size: 0.875rem;
        overflow-wrap: anywhere;
    }

    .booking-table thead th:first-child,
    .booking-table tbody td:first-child {
        text-align: center;
    }

    .booking-reference {
        display: inline-flex;
        max-width: 100%;
        box-sizing: border-box;
        align-items: center;
        justify-content: center;
        padding: 0.2rem 0.3rem;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        white-space: nowrap;
        letter-spacing: 0.02em;
        font-variant-numeric: tabular-nums;
    }

    .booking-account-label,
    .booking-contact-email {
        display: block;
        margin-top: 0.2rem;
        color: #64748b;
        font-size: 0.75rem;
    }

    .booking-payment-status {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.55rem;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .booking-payment-status.paid {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
    }

    .booking-payment-status.partial {
        border-color: #fde68a;
        background: #fffbeb;
        color: #a16207;
    }

    .booking-payment-status.not-paid {
        border-color: #e2e8f0;
        background: #f8fafc;
        color: #475569;
    }

    .booking-view-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 36px;
        padding: 0.4rem 0.65rem;
        border: 1px solid #bfdbfe;
        border-radius: 6px;
        color: #1d4ed8;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        white-space: nowrap;
    }

    .booking-view-action:hover,
    .booking-view-action:focus-visible {
        border-color: #2563eb;
        background: #eff6ff;
        color: #1d4ed8;
    }

    .booking-table .booking-empty {
        padding: 40px 20px;
        color: #64748b;
        text-align: center;
    }

    @media (max-width: 767.98px) {
        .booking-tabs {
            gap: 14px;
        }

        .booking-tab {
            padding: 10px 2px;
        }
    }
</style>
</div>
@if(session('completed_success'))
    <script>
        (() => {
            const toast = document.getElementById('booking-completion-toast');
            toast?.querySelector('button')?.addEventListener('click', () => toast.remove());
            window.setTimeout(() => toast?.remove(), 5000);
        })();
    </script>
@endif
@endsection
