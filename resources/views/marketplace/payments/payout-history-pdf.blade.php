<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payout History</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            margin: 0;
            padding: 24px;
            background: #ffffff;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 18px;
        }

        .brand {
            display: table-cell;
            width: 50%;
            font-size: 24px;
            font-weight: 700;
            color: #14532d;
        }

        .title {
            display: table-cell;
            width: 50%;
            text-align: right;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }

        .meta {
            margin-bottom: 18px;
            font-size: 12px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 11px;
        }

        th, td {
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
            vertical-align: top;
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }

        .amount {
            white-space: nowrap;
            text-align: right;
            font-weight: 700;
        }

        .status {
            white-space: nowrap;
            font-weight: 700;
        }

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">TRESCA Hub</div>
        <div class="title">Payout History</div>
    </div>

    <div class="meta">
        Generated: {{ $generatedAt->format('F j, Y g:i A') }}<br>
        Total records: {{ $payouts->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Payout Date</th>
                <th>Recipient</th>
                <th>Reference</th>
                <th class="amount">Amount</th>
                <th>Status</th>
                <th>Sent by</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payouts as $payout)
                <tr>
                    <td>{{ $payout->created_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td>{{ $payout->mobile_phone ?? '—' }}</td>
                    <td>{{ $payout->request_transaction_id ?: ($payout->reference_id ?: '—') }}</td>
                    <td class="amount">RWF {{ number_format((float) $payout->amount, 2) }}</td>
                    <td class="status">{{ ucfirst((string) ($payout->status ?? 'unknown')) }}</td>
                    <td>{{ $payout->user->name ?? '—' }}</td>
                    <td>{{ $payout->reason ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No payout records available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
