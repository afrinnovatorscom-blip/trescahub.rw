<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tresca Hub Orders History</title>
    <style>
        @page { margin: 28px 26px 40px; }
        body { margin: 0; color: #172033; font-family: "DejaVu Sans", sans-serif; font-size: 7.3pt; line-height: 1.35; }
        .header { width: 100%; margin-bottom: 12px; padding-bottom: 9px; border-bottom: 2px solid #1e3a5f; }
        .brand { color: #14243a; font-size: 16pt; font-weight: bold; letter-spacing: .2px; }
        .brand-accent { color: #2563eb; }
        .subtitle { margin-top: 3px; color: #64748b; font-size: 7pt; letter-spacing: .5px; }
        .header-meta { color: #475569; font-size: 7.2pt; text-align: right; }
        .report-title { margin: 0 0 4px; color: #14243a; font-size: 13pt; font-weight: bold; }
        .summary { width: 100%; margin-bottom: 11px; border-collapse: collapse; }
        .summary td { padding: 7px 9px; border: 1px solid #d8dee8; background: #f7f9fc; }
        .summary-label { color: #64748b; font-size: 6.7pt; font-weight: bold; text-transform: uppercase; }
        .summary-value { margin-top: 2px; color: #172033; font-size: 10pt; font-weight: bold; }
        .filter-note { margin: 0 0 8px; color: #64748b; font-size: 7pt; }
        table.orders { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .orders thead { display: table-header-group; }
        .orders tr { page-break-inside: avoid; }
        .orders th { padding: 6px 4px; color: #fff; background: #1e3a5f; border: 1px solid #1e3a5f; font-size: 6.3pt; font-weight: bold; text-align: left; vertical-align: middle; }
        .orders td { padding: 5px 4px; border: 1px solid #d8dee8; color: #273449; font-size: 6.3pt; vertical-align: top; overflow-wrap: break-word; }
        .orders tbody tr:nth-child(even) td { background: #f7f9fc; }
        .orders .amount { text-align: right; white-space: nowrap; }
        .orders .date { white-space: nowrap; }
        .orders .reference { font-weight: bold; }
        .total-row td { color: #14243a; background: #eaf0f8 !important; border-top: 2px solid #1e3a5f; font-weight: bold; }
        .empty-row td { padding: 18px; color: #64748b; text-align: center; }
        .product-line { margin-bottom: 2px; }
        .product-line:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">TRESCA <span class="brand-accent">HUB</span></div>
                <div class="subtitle">ADMINISTRATIVE ORDER REGISTER</div>
            </td>
            <td class="header-meta">
                <h1 class="report-title">Orders History</h1>
                <div>Reporting period: {{ $report['period']['formatted'] }}</div>
                <div>Generated: {{ now()->format('d M Y, H:i') }} Africa/Kigali</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td style="width: 50%">
                <div class="summary-label">Orders in this export</div>
                <div class="summary-value">{{ number_format($report['total_orders']) }}</div>
            </td>
            <td style="width: 50%">
                <div class="summary-label">Combined order total (RWF)</div>
                <div class="summary-value">{{ number_format($report['total_order_value'], 2, '.', ',') }}</div>
            </td>
        </tr>
    </table>

    @if(collect($report['filters'])->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
        <p class="filter-note">
            Filters:
            @if($report['filters']['customer'] ?? null) Customer: {{ $report['filters']['customer'] }}; @endif
            @if($report['filters']['phone'] ?? null) Phone: {{ $report['filters']['phone'] }}; @endif
            @if($report['filters']['reference'] ?? null) Reference: {{ $report['filters']['reference'] }}; @endif
            @if($report['filters']['status'] ?? null) Status: {{ \Illuminate\Support\Str::headline($report['filters']['status']) }}; @endif
            @if($report['filters']['from'] ?? null) From: {{ \Carbon\Carbon::parse($report['filters']['from'])->format('d M Y') }}; @endif
            @if($report['filters']['to'] ?? null) To: {{ \Carbon\Carbon::parse($report['filters']['to'])->format('d M Y') }}; @endif
        </p>
    @endif

    <table class="orders">
        <colgroup>
            <col style="width: 12%">
            <col style="width: 12%">
            <col style="width: 11%">
            <col style="width: 8%">
            <col style="width: 17%">
            <col style="width: 18%">
            <col style="width: 9%">
            <col style="width: 7%">
            <col style="width: 6%">
        </colgroup>
        <thead>
            <tr>
                <th>Order Reference</th>
                <th>Order Date</th>
                <th>Customer</th>
                <th>Phone</th>
                <th>Delivery Address</th>
                <th>Products / Qty</th>
                <th class="amount">Order Total (RWF)</th>
                <th>Current Status</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['orders'] as $order)
                <tr>
                    <td class="reference">{{ $order->reference }}</td>
                    <td class="date">{{ $order->created_at?->format('d M Y, H:i') ?? 'Not recorded' }}</td>
                    <td>{{ $order->customer_name ?: 'Not recorded' }}</td>
                    <td>{{ $order->phone ?: 'Not recorded' }}</td>
                    <td>{{ $order->delivery_address ?: 'Not recorded' }}</td>
                    <td>
                        @forelse($order->items as $item)
                            <div class="product-line">{{ $item->product_name ?: 'Product unavailable' }} × {{ $item->quantity }}</div>
                        @empty
                            Not recorded
                        @endforelse
                    </td>
                    <td class="amount">{{ number_format((float) $order->total, 2, '.', ',') }}</td>
                    <td>{{ \Illuminate\Support\Str::headline($order->fulfillment_status ?: 'Not recorded') }}</td>
                    <td class="date">{{ $order->updated_at?->format('d M Y, H:i') ?? 'Not recorded' }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty-row">No orders match the selected filters.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="6">Combined order total (RWF)</td>
                <td class="amount">{{ number_format($report['total_order_value'], 2, '.', ',') }}</td>
                <td colspan="2">{{ number_format($report['total_orders']) }} orders</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
