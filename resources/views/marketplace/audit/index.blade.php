@extends('front.layouts.admin')
@section('page-title', 'Audit')

@push('styles')
<style>
    .audit-log-page { max-width: 1600px; }
    .audit-log-heading {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 22px;
    }
    .audit-log-heading-icon {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: rgba(0, 212, 170, 0.12);
        color: #008f78;
        font-size: 1.15rem;
    }
    .audit-log-heading h1 {
        margin: 0;
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 700;
    }
    .audit-log-heading p {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 0.875rem;
    }
    .audit-log-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .audit-log-table {
        min-width: 760px;
        margin: 0;
    }
    .audit-log-table thead th {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.045em;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .audit-log-table tbody td {
        padding: 13px 16px;
        color: #334155;
        vertical-align: middle;
    }
    .audit-log-table tbody tr:last-child td { border-bottom: 0; }
    .audit-log-actor { color: #0f172a; font-weight: 600; }
    .audit-log-action {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        background: #f1f5f9;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 600;
        overflow-wrap: anywhere;
    }
    .audit-log-record { color: #0f172a; font-weight: 500; }
    .audit-log-record-id,
    .audit-log-ip { color: #64748b; font-size: 0.8rem; }
    .audit-log-date { white-space: nowrap; }
    .audit-log-pagination {
        display: flex;
        justify-content: center;
        padding: 12px 16px;
        border-top: 1px solid #e2e8f0;
    }
    .audit-log-pagination nav {
        width: 100%;
        margin-bottom: 0;
    }
    .audit-log-pagination nav > div:last-child {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .audit-log-pagination nav > div:last-child p {
        margin: 0;
        color: #64748b;
        font-size: 0.8rem;
    }
    .audit-log-pagination nav > div:last-child > div:last-child > a,
    .audit-log-pagination nav > div:last-child > div:last-child > span > span,
    .audit-log-pagination nav > div:last-child > div:last-child > span > a,
    .audit-log-pagination nav > div:first-child > span,
    .audit-log-pagination nav > div:first-child > a {
        display: inline-flex;
        min-width: 32px;
        min-height: 32px;
        align-items: center;
        justify-content: center;
        padding: 5px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 7px;
        background: #fff;
        color: #475569;
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1;
        text-decoration: none;
        box-shadow: none;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
    }
    .audit-log-pagination nav svg {
        display: block;
        width: 14px !important;
        height: 14px !important;
    }
    .audit-log-pagination nav [aria-current="page"] > span {
        border-color: #00d4aa;
        background: #00d4aa;
        color: #0b0f1a;
    }
    .audit-log-pagination nav [aria-disabled="true"] > span {
        background: #f8fafc;
        color: #a0a9b8;
        cursor: not-allowed;
    }
    .audit-log-pagination nav a:hover {
        z-index: 1;
        border-color: #94a3b8 !important;
        background: #f8fafc !important;
        color: #0f172a !important;
    }
    .audit-log-pagination nav a:focus-visible {
        outline: 3px solid rgba(0, 212, 170, 0.28);
        outline-offset: 2px;
    }
    @media (max-width: 575.98px) {
        .audit-log-heading { margin-bottom: 16px; }
        .audit-log-card { margin-right: -4px; margin-left: -4px; }
        .audit-log-pagination { padding: 10px 8px; }
        .audit-log-pagination nav > div:last-child { justify-content: center; }
        .audit-log-pagination nav > div:last-child > div:first-child { width: 100%; text-align: center; }
        .audit-log-pagination nav > div:first-child { justify-content: center; }
        .audit-log-pagination nav > div:last-child > div:last-child > a,
        .audit-log-pagination nav > div:last-child > div:last-child > span > span,
        .audit-log-pagination nav > div:last-child > div:last-child > span > a,
        .audit-log-pagination nav > div:first-child > span,
        .audit-log-pagination nav > div:first-child > a { min-width: 30px; min-height: 30px; padding: 4px 7px; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 audit-log-page">
    <div class="audit-log-heading">
        <span class="audit-log-heading-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
        <div>
            <h1>Audit log</h1>
            <p>Review recorded account and system activity.</p>
        </div>
    </div>

    <div class="audit-log-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle audit-log-table">
                <thead>
                    <tr>
                        <th scope="col">Date &amp; time</th>
                        <th scope="col">User</th>
                        <th scope="col">Action</th>
                        <th scope="col">Affected record</th>
                        <th scope="col">IP address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="audit-log-date">
                                <span class="d-block text-dark fw-semibold">{{ $log->created_at?->format('M d, Y') }}</span>
                                <span class="small text-muted">{{ $log->created_at?->format('H:i:s') }}</span>
                            </td>
                            <td class="audit-log-actor">{{ $log->actor->name ?? 'System' }}</td>
                            <td><span class="audit-log-action">{{ $log->action }}</span></td>
                            <td>
                                <span class="audit-log-record">{{ $log->auditable_type ? class_basename($log->auditable_type) : 'Record' }}</span>
                                <span class="audit-log-record-id">#{{ $log->auditable_id ?? '-' }}</span>
                            </td>
                            <td class="audit-log-ip">{{ $log->ip_address ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">No audit activity to display.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="audit-log-pagination">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
