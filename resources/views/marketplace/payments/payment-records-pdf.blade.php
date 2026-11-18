<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payment Records</title>
    <style>
        @page { margin: 30px 28px 42px; }
        body { color: #172033; font-family: "DejaVu Sans", sans-serif; font-size: 7.5pt; line-height: 1.35; margin: 0; }
        .header { border-bottom: 2px solid #1e3a5f; margin-bottom: 12px; padding-bottom: 8px; width: 100%; }
        .brand { color: #14243a; font-size: 16pt; font-weight: bold; }
        .brand-accent { color: #2563eb; }
        .subtitle { color: #64748b; font-size: 6.5pt; letter-spacing: .5px; margin-top: 3px; }
        .report-title { color: #14243a; font-size: 13pt; font-weight: bold; margin: 0 0 4px; }
        .header-meta { color: #475569; font-size: 7pt; text-align: right; }
        table.records { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .records thead { display: table-header-group; }
        .records tr { page-break-inside: avoid; }
        .records th { background: #1e3a5f; border: 1px solid #1e3a5f; color: #fff; font-size: 6.5pt; font-weight: bold; padding: 6px 5px; text-align: left; vertical-align: middle; }
        .records td { border: 1px solid #d8dee8; color: #273449; font-size: 6.6pt; overflow-wrap: break-word; padding: 6px 5px; vertical-align: top; }
        .records tbody tr:nth-child(even) td { background: #f7f9fc; }
        .date { white-space: nowrap; }
        .amount { font-weight: bold; text-align: right; white-space: nowrap; }
        .muted { color: #64748b; }
        .order-line { margin-bottom: 3px; }
        .order-line:last-child { margin-bottom: 0; }
        .empty-row td { color: #64748b; padding: 18px; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">TRESCA <span class="brand-accent">HUB</span></div>
                <div class="subtitle">ADMINISTRATIVE PAYMENT REGISTER</div>
            </td>
            <td class="header-meta">
                <h1 class="report-title">Payment Records</h1>
                <div>Generated: {{ $generatedAt->format('d M Y, H:i') }}</div>
                <div>Total records: {{ number_format($transactions->count()) }}</div>
            </td>
        </tr>
    </table>

    <table class="records">
        <colgroup>
            <col style="width: 12%">
            <col style="width: 15%">
            <col style="width: 35%">
            <col style="width: 12%">
            <col style="width: 14%">
            <col style="width: 12%">
        </colgroup>
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Customer / Payer</th>
                <th>Order Information</th>
                <th class="amount">Amount</th>
                <th>Payment Method</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $transaction)
                <tr>
                    <td class="date">
                        {{ $transaction->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}
                        @if($transaction->paid_at)
                            <div class="muted">Paid {{ $transaction->paid_at->format('d M Y, H:i') }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $transaction->order?->customer_name ?: '—' }}
                        <div class="muted">{{ $transaction->phone ?: '—' }}</div>
                    </td>
                    <td>
                        @if($transaction->order)
                            @forelse($transaction->order->items as $item)
                                <div class="order-line">
                                    <strong>{{ $item->product_name }}</strong>
                                    @if($item->variation_label || $item->variation_value)
                                        <span class="muted"> — {{ trim(($item->variation_label ? $item->variation_label.': ' : '').($item->variation_value ?? '')) }}</span>
                                    @endif
                                    <span> × {{ $item->quantity }}</span>
                                    <div class="muted">
                                        Unit {{ number_format((float) $item->unit_price, 2) }} {{ $transaction->currency ?: 'RWF' }}
                                        · Line total {{ number_format((float) $item->line_total, 2) }} {{ $transaction->currency ?: 'RWF' }}
                                    </div>
                                </div>
                            @empty
                                @if($transaction->order->productRequest)
                                    <strong>{{ $transaction->order->productRequest->product_name }}</strong>
                                    <div>Quantity: {{ $transaction->order->productRequest->quantity }}</div>
                                    @if($transaction->order->productRequest->variation_label || $transaction->order->productRequest->variation_value)
                                        <div class="muted">
                                            {{ $transaction->order->productRequest->variation_label }} {{ $transaction->order->productRequest->variation_value }}
                                        </div>
                                    @endif
                                    @if($transaction->order->productRequest->instructions)
                                        <div class="muted">{{ $transaction->order->productRequest->instructions }}</div>
                                    @endif
                                @else
                                    <span class="muted">No item details recorded for this order</span>
                                @endif
                            @endforelse
                        @else
                            <span class="muted">No associated order information</span>
                        @endif
                    </td>
                    <td class="amount">{{ number_format((float) $transaction->amount, 2) }} {{ $transaction->currency ?: 'RWF' }}</td>
                    <td>
                        {{ $transaction->method === 'airtel_money' ? 'Airtel Money' : ($transaction->method === 'mtn_mobile_money' ? 'MTN MoMo' : $transaction->method) }}
                        @if($transaction->provider)
                            <div class="muted">{{ $transaction->provider }}</div>
                        @endif
                    </td>
                    <td>
                        {{ ucfirst((string) $transaction->status) }}
                        @if($transaction->failure_reason)
                            <div class="muted">{{ $transaction->failure_reason }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty-row">No payment records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
