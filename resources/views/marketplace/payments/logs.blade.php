@extends('front.layouts.admin')
@section('page-title', 'IntouchPay logs')
@section('content')
<div class="pay-admin">
    <div class="pay-heading">
        <div>
            <h1><i class="bi bi-journal-code"></i> IntouchPay logs</h1>
            <p>Every payment request we sent, every callback IntouchPay sent us, and every status check — with the raw data.</p>
        </div>
    </div>

    @include('marketplace.payments._styles')

    <form method="GET" class="pay-filters">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Reference, order, message, code">
        <select name="type" class="form-select">
            <option value="">All types</option>
            @foreach(\App\Models\PaymentLog::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label class="d-flex align-items-center gap-1 small text-muted">
            <input type="checkbox" name="unmatched" value="1" @checked(request()->boolean('unmatched'))> Unmatched callbacks only
        </label>
        <button class="btn btn-dark btn-sm">Filter</button>
        @if(array_filter($filters))
            <a href="{{ route('marketplace.payments.logs') }}" class="btn btn-link btn-sm">Clear</a>
        @endif
    </form>

    <div class="pay-card">
        <div class="table-responsive">
            <table class="table pay-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Order / reference</th>
                        <th>Code</th>
                        <th>Status / message</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                {{ $log->created_at->format('M j, Y') }}
                                <div class="muted">{{ $log->created_at->format('g:i:s A') }}</div>
                            </td>
                            <td><span class="pay-badge is-{{ $log->type }}">{{ \App\Models\PaymentLog::TYPES[$log->type] ?? $log->type }}</span></td>
                            <td>
                                @if($log->transaction?->order)
                                    <a href="{{ route('marketplace.orders.show', $log->transaction->order) }}" class="fw-semibold text-decoration-none">{{ $log->transaction->order->reference }}</a>
                                @elseif(! $log->payment_transaction_id)
                                    <span class="pay-badge is-failed">Unmatched</span>
                                @endif
                                <div class="pay-mono muted">{{ $log->request_transaction_id ?? '—' }}</div>
                            </td>
                            <td class="pay-mono">{{ $log->response_code ?? '—' }}</td>
                            <td style="max-width: 22rem">
                                @if($log->status)<strong>{{ $log->status }}</strong>@endif
                                <div class="muted">{{ $log->message }}</div>
                                @if($log->ip_address)<div class="muted">IP {{ $log->ip_address }}</div>@endif
                            </td>
                            <td>
                                <details class="pay-details">
                                    <summary>Show raw data</summary>
                                    <pre>{{ json_encode(['sent' => $log->payload, 'received' => $log->response], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="pay-empty">No log entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</div>
@endsection
