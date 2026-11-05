<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tresca Hub Finance Report</title>
    <style>
        @page { margin: 28px 26px 38px; }
        body { margin: 0; color: #0f172a; font-family: "DejaVu Sans", sans-serif; font-size: 8pt; line-height: 1.35; }
        .header { width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .brand { font-size: 16pt; font-weight: bold; }
        .brand span { color: #2563eb; }
        .subtitle { color: #64748b; font-size: 7pt; margin-top: 3px; }
        .meta { text-align: right; color: #475569; font-size: 8pt; }
        h1 { margin: 0 0 5px; font-size: 13pt; color: #0f172a; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 0 0 12px; }
        .summary td { width: 50%; padding: 8px 10px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .label { color: #64748b; font-size: 7pt; font-weight: bold; }
        .value { margin-top: 3px; font-size: 12pt; font-weight: bold; }
        table.orders { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .orders thead { display: table-header-group; }
        .orders tr { page-break-inside: avoid; }
        .orders th { padding: 6px 5px; background: #f1f5f9; color: #334155; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; text-align: left; font-size: 7pt; }
        .orders td { padding: 5px; border-bottom: 1px solid #e2e8f0; color: #1e293b; vertical-align: top; font-size: 7.3pt; overflow-wrap: break-word; }
        .orders tbody tr:nth-child(even) td { background: #fafafa; }
        .orders .amount { text-align: right; white-space: nowrap; }
        .orders .total-row td { background: #eff6ff; border-top: 2px solid #2563eb; font-weight: bold; }
        .muted { color: #64748b; }
        .empty { padding: 18px !important; color: #64748b !important; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">TRESCA <span>HUB</span></div>
                <div class="subtitle">FINANCE REPORT</div>
            </td>
            <td class="meta">
                <h1>Finance Report</h1>
                <div>Reporting period: {{ $financeReport['period']['formatted'] }}</div>
                <div class="muted">Generated: {{ now()->format('d M Y, H:i') }} Africa/Kigali</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($financeReport['total_orders']) }}</div>
            </td>
            <td>
                <div class="label">Total Order Value</div>
                <div class="value">RWF {{ number_format($financeReport['total_order_value'], 2, '.', ',') }}</div>
            </td>
        </tr>
    </table>

    <table class="orders">
        <colgroup>
            <col style="width: 12%">
            <col style="width: 17%">
            <col style="width: 15%">
            <col style="width: 27%">
            <col style="width: 15%">
            <col style="width: 14%">
        </colgroup>
        <thead>
            <tr>
                <th>Order Date</th>
                <th>Order Reference</th>
                <th>Customer</th>
                <th>Delivery Address</th>
                <th class="amount">Order Amount (RWF)</th>
                <th>Order Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($financeReport['orders'] as $order)
                <tr>
                    <td>{{ $order->created_at?->format('d M Y') }}</td>
                    <td>{{ $order->reference }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->delivery_address }}</td>
                    <td class="amount">{{ number_format((float) $order->total, 2, '.', ',') }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($order->fulfillment_status) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No orders match the selected filters.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4">Total Order Value (RWF)</td>
                <td class="amount">{{ number_format($financeReport['total_order_value'], 2, '.', ',') }}</td>
                <td>{{ number_format($financeReport['total_orders']) }} orders</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
