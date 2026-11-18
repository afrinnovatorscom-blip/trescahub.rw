@extends('front.layouts.admin')
@section('page-title', 'Payments')
@section('content')
<style>
    .payment-records-head { align-items: center; display: flex; justify-content: space-between; gap: .75rem; }
    .payment-records-head .btn { flex: 0 0 auto; white-space: nowrap; }
    .payment-records-pagination { align-items: center; display: flex; justify-content: flex-end; gap: 8px; }
    .payment-records-pagination-button { align-items: center; background: #fff; border: 1px solid #e2e8f0; border-radius: 7px; color: #475569; display: inline-flex; font-size: 13px; font-weight: 600; gap: 6px; min-height: 36px; padding: 6px 11px; text-decoration: none; }
    .payment-records-pagination-button:hover { background: #f8fafc; border-color: #cbd5e1; color: #1d4ed8; }
    .payment-records-pagination-button:focus-visible { outline: 3px solid rgba(37, 99, 235, .25); outline-offset: 2px; }
    .payment-records-pagination-button.is-disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }
    .payment-records-pagination-page { color: #64748b; font-size: 12px; min-width: 64px; text-align: center; }
    @media (max-width: 400px) {
        .payment-records-head { align-items: flex-start; }
        .payment-records-head .btn { font-size: .75rem; padding: .3rem .45rem; }
        .payment-records-pagination { justify-content: center; }
    }
</style>
<div class="pay-admin">
    <div class="pay-heading">
        <div>
            <h1><i class="bi bi-credit-card"></i> Payments</h1>
            <p>Every MTN MoMo and Airtel Money payment attempt made at checkout through IntouchPay.</p>
        </div>
    </div>

    @include('marketplace.payments._styles')
    @include('marketplace.partials.flash')

    <div class="pay-stats">
        <div class="pay-stat"><span>Collected (all time)</span><strong>RWF {{ number_format($stats['collected']) }}</strong></div>
        <div class="pay-stat"><span>Collected today</span><strong>RWF {{ number_format($stats['today']) }}</strong></div>
        <div class="pay-stat"><span>Successful payments</span><strong>{{ number_format($stats['completed']) }}</strong></div>
        <div class="pay-stat"><span>Waiting for approval</span><strong>{{ number_format($stats['pending']) }}</strong></div>
        <div class="pay-stat"><span>Failed</span><strong>{{ number_format($stats['failed']) }}</strong></div>
    </div>

    <form method="GET" class="pay-filters">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Order, customer, phone, reference">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['completed' => 'Completed', 'pending' => 'Pending', 'failed' => 'Failed'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="method" class="form-select">
            <option value="">All methods</option>
            <option value="mtn_mobile_money" @selected(($filters['method'] ?? '') === 'mtn_mobile_money')>MTN Mobile Money</option>
            <option value="airtel_money" @selected(($filters['method'] ?? '') === 'airtel_money')>Airtel Money</option>
        </select>
        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control" aria-label="From date">
        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control" aria-label="To date">
        <button class="btn btn-dark btn-sm">Filter</button>
        @if(array_filter($filters))
            <a href="{{ route('marketplace.payments.index') }}" class="btn btn-link btn-sm">Clear</a>
        @endif
    </form>

    <div class="pay-card">
        <div class="pay-card-head payment-records-head">
            <span>Payment records</span>
            <a href="{{ route('marketplace.payments.export') }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                <span>Download PDF</span>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table pay-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order</th>
                        <th>Method / phone</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td class="text-nowrap">
                                {{ $transaction->created_at->format('M j, Y') }}
                                <div class="muted">{{ $transaction->created_at->format('g:i A') }}</div>
                            </td>
                            <td>
                                @if($transaction->order)
                                    <a href="{{ route('marketplace.orders.show', $transaction->order) }}" class="fw-semibold text-decoration-none">{{ $transaction->order->reference }}</a>
                                    <div class="muted">{{ $transaction->order->customer_name }}</div>
                                @else
                                    <span class="muted">Deleted order</span>
                                @endif
                            </td>
                            <td>
                                {{ $transaction->method === 'airtel_money' ? 'Airtel Money' : 'MTN MoMo' }}
                                <div class="muted">{{ $transaction->phone }}</div>
                            </td>
                            <td class="text-end fw-bold text-nowrap">RWF {{ number_format((float) $transaction->amount) }}</td>
                            <td>
                                <span class="pay-badge is-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                                @if($transaction->paid_at)
                                    <div class="muted">Paid {{ $transaction->paid_at->format('M j, g:i A') }}</div>
                                @elseif($transaction->failure_reason)
                                    <div class="muted" style="max-width: 16rem">{{ $transaction->failure_reason }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="pay-mono">{{ $transaction->request_transaction_id }}</div>
                                @if($transaction->transaction_id)
                                    <div class="muted pay-mono">IntouchPay: {{ $transaction->transaction_id }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('marketplace.payments.logs', ['search' => $transaction->request_transaction_id]) }}" class="pay-icon-btn text-decoration-none" title="View IntouchPay logs"><i class="bi bi-journal-code"></i></a>
                                @if($transaction->status === 'pending' && $transaction->transaction_id)
                                    <form method="POST" action="{{ route('marketplace.payments.recheck', $transaction) }}" class="d-inline">
                                        @csrf
                                        <button class="pay-icon-btn" title="Re-check status with IntouchPay"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="pay-empty">No payments match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->total() > 20)
            <nav class="payment-records-pagination px-3 py-3 border-top" aria-label="Payment records pagination">
                @if($transactions->currentPage() > 1)
                    <a class="payment-records-pagination-button" href="{{ $transactions->url($transactions->currentPage() - 1) }}" rel="prev">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Previous</span>
                    </a>
                @else
                    <span class="payment-records-pagination-button is-disabled" aria-disabled="true">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i><span>Previous</span>
                    </span>
                @endif
                <span class="payment-records-pagination-page">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
                @if($transactions->currentPage() < $transactions->lastPage())
                    <a class="payment-records-pagination-button" href="{{ $transactions->url($transactions->currentPage() + 1) }}" rel="next">
                        <span>Next</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @else
                    <span class="payment-records-pagination-button is-disabled" aria-disabled="true">
                        <span>Next</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </span>
                @endif
            </nav>
        @endif
    </div>
</div>
@endsection
