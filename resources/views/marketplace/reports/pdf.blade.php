<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tresca Hub Marketplace Report — {{ $report['period']['formatted'] }}</title>
    <style>
        @page {
            margin: 26px 24px 36px 24px;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        /* ── Header ── */
        .pdf-header {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .pdf-brand {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.5px;
        }
        .pdf-brand span {
            color: #2563eb;
        }
        .pdf-subtitle {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .pdf-meta {
            text-align: right;
            font-size: 8pt;
            color: #475569;
        }
        .pdf-period-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            margin-top: 4px;
        }

        /* ── Section Headings ── */
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Summary Grid (Table-based for DomPDF compatibility) ── */
        .summary-grid {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 6px;
        }
        .summary-cell {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            vertical-align: top;
            width: 25%;
        }
        .summary-cell-title {
            font-size: 7pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .summary-cell-value {
            font-size: 13pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.1;
        }
        .summary-cell-sub {
            font-size: 7pt;
            color: #64748b;
            margin-top: 3px;
        }
        .text-success { color: #16a34a !important; }
        .text-primary { color: #2563eb !important; }
        .text-warning { color: #d97706 !important; }
        .text-danger { color: #dc2626 !important; }

        /* ── Tables ── */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            page-break-inside: auto;
        }
        table.data-table thead {
            display: table-header-group;
        }
        table.data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table.data-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 7.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        table.data-table td {
            font-size: 8pt;
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) td {
            background: #fafafa;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-muted { color: #64748b; }
        .small { font-size: 7.5pt; }

        /* ── Badges / Pills ── */
        .pill {
            display: inline-block;
            font-size: 6.5pt;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .pill-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .pill-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .pill-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .pill-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .pill-neutral { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

        /* ── Callout box ── */
        .callout-box {
            background: #f8fafc;
            border-left: 3px solid #2563eb;
            padding: 6px 10px;
            font-size: 7.5pt;
            color: #334155;
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .empty-row td {
            text-align: center;
            padding: 16px;
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>

    {{-- Embedded PHP for Footer Page Numbers in DomPDF --}}
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Tresca Hub Marketplace — Confidential Management Report — Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans, Helvetica, sans-serif", "normal");
            $size = 7.5;
            $color = array(0.45, 0.5, 0.55);
            $pdf->page_text(24, 814, $text, $font, $size, $color, 0.0, 0.0, 0.0);
        }
    </script>

    {{-- Header --}}
    <table class="pdf-header">
        <tr>
            <td style="vertical-align: top;">
                <div class="pdf-brand">TRESCA <span>HUB</span></div>
                <div class="pdf-subtitle">Marketplace & Operations Management</div>
            </td>
            <td class="pdf-meta" style="vertical-align: top;">
                <div><strong>OPERATIONAL REPORT</strong></div>
                <div class="pdf-period-badge">{{ $report['period']['formatted'] }}</div>
                <div class="small text-muted" style="margin-top: 3px;">
                    Generated: {{ now()->format('d M Y, H:i') }} UTC
                </div>
            </td>
        </tr>
    </table>

    {{-- EXECUTIVE SUMMARY --}}
    <div class="section-title">1. Executive Summary</div>
    <table class="summary-grid">
        <tr>
            <td class="summary-cell">
                <div class="summary-cell-title">Product Orders Placed</div>
                <div class="summary-cell-value text-primary">{{ number_format($report['summary']['orders_total']) }}</div>
                <div class="summary-cell-sub">{{ $report['summary']['orders_active'] }} non-cancelled</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Total Units Sold</div>
                <div class="summary-cell-value">{{ number_format($report['summary']['units_sold']) }}</div>
                <div class="summary-cell-sub">Across valid customer orders</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Total Booked Value</div>
                <div class="summary-cell-value">{{ number_format($report['summary']['gross_order_value'], 0, '.', ',') }} <span class="small">RWF</span></div>
                <div class="summary-cell-sub">Gross order demand</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Collected Payments</div>
                @if($report['permissions']['can_view_finance'])
                    <div class="summary-cell-value text-success">{{ number_format($report['summary']['collected_revenue'], 0, '.', ',') }} <span class="small">RWF</span></div>
                    <div class="summary-cell-sub">Actual verified collections</div>
                @else
                    <div class="summary-cell-value text-muted">Restricted</div>
                    <div class="summary-cell-sub">Requires finance access</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="summary-cell">
                <div class="summary-cell-title">Awaiting Confirmation</div>
                <div class="summary-cell-value text-warning">{{ $report['summary']['orders_awaiting_confirmation'] }}</div>
                <div class="summary-cell-sub">Orders pending review</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">In Processing / Prep</div>
                <div class="summary-cell-value text-primary">{{ $report['summary']['orders_processing'] }}</div>
                <div class="summary-cell-sub">Confirmed & in fulfillment</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Delivered / Done</div>
                <div class="summary-cell-value text-success">{{ $report['summary']['orders_delivered'] }}</div>
                <div class="summary-cell-sub">{{ $report['summary']['orders_completed'] }} fully completed</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Cancelled Orders</div>
                <div class="summary-cell-value text-danger">{{ $report['summary']['orders_cancelled'] }}</div>
                <div class="summary-cell-sub">Voided in period</div>
            </td>
        </tr>
        <tr>
            <td class="summary-cell">
                <div class="summary-cell-title">Service Requests</div>
                <div class="summary-cell-value">{{ $report['summary']['services_total'] }}</div>
                <div class="summary-cell-sub">{{ $report['summary']['services_completed'] }} completed</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Inventory Health</div>
                <div class="summary-cell-value">{{ $report['inventory']['tracked_count'] }} <span class="small">tracked</span></div>
                <div class="summary-cell-sub">{{ $report['summary']['low_stock_count'] }} low stock alerts</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Delivery Rate</div>
                <div class="summary-cell-value text-success">{{ $report['orders']['delivery_success_rate'] }}%</div>
                <div class="summary-cell-sub">{{ $report['orders']['delivery_tasks_delivered'] }} delivered tasks</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Inquiry Conversion</div>
                <div class="summary-cell-value text-primary">{{ $report['products']['requests_conversion_rate'] }}%</div>
                <div class="summary-cell-sub">{{ $report['products']['requests_converted'] }} of {{ $report['products']['requests_total'] }} converted</div>
            </td>
        </tr>
    </table>

    {{-- SECTION 2: PRODUCT AND SALES PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'products']))
    <div class="section-title">2. Product & Sales Performance</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Product / SKU</th>
                <th style="width: 22%;">Category & Vendor</th>
                <th class="text-center" style="width: 10%;">Ordered</th>
                <th class="text-center" style="width: 10%;">Delivered</th>
                <th class="text-center" style="width: 10%;">Cancelled</th>
                <th class="text-right" style="width: 16%;">Booked Value (RWF)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['products']['best_sellers'] as $item)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $item['name'] }}</div>
                        <div class="small text-muted">SKU: {{ $item['sku'] }}</div>
                    </td>
                    <td>
                        <div>{{ $item['category'] }}</div>
                        <div class="small text-muted">{{ $item['vendor'] }}</div>
                    </td>
                    <td class="text-center fw-bold">{{ number_format($item['units_ordered']) }}</td>
                    <td class="text-center text-success fw-bold">{{ number_format($item['units_delivered']) }}</td>
                    <td class="text-center text-danger">{{ number_format($item['units_cancelled']) }}</td>
                    <td class="text-right fw-bold">{{ number_format($item['gross_revenue'], 0, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">No product orders recorded for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Order Source Comparison --}}
    <table class="data-table" style="margin-top: 6px;">
        <thead>
            <tr>
                <th>Order Channel / Source</th>
                <th class="text-center">Orders</th>
                <th class="text-right">Booked Value (RWF)</th>
                <th class="text-right">Paid Value (RWF)</th>
                <th class="text-center">Delivery Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['products']['order_sources'] as $src)
                <tr>
                    <td class="fw-bold">{{ $src['label'] }}</td>
                    <td class="text-center">{{ $src['total_orders'] }}</td>
                    <td class="text-right fw-bold">{{ number_format($src['gross_value'], 0, '.', ',') }}</td>
                    <td class="text-right text-success">{{ number_format($src['paid_value'], 0, '.', ',') }}</td>
                    <td class="text-center">{{ $src['delivery_rate'] }}%</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="5">No order sources found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- SECTION 3: SERVICE REQUEST PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'services']))
    <div class="section-title">3. Service Request Performance</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 40%;">Service Category</th>
                <th class="text-center" style="width: 15%;">Requests</th>
                <th class="text-center" style="width: 15%;">Pending</th>
                <th class="text-center" style="width: 15%;">Reviewed</th>
                <th class="text-center" style="width: 15%;">Completed</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['services']['categories'] as $svc)
                <tr>
                    <td class="fw-bold">{{ $svc['category'] }}</td>
                    <td class="text-center fw-bold">{{ $svc['total'] }}</td>
                    <td class="text-center">{{ $svc['pending'] }}</td>
                    <td class="text-center">{{ $svc['reviewed'] }}</td>
                    <td class="text-center text-success fw-bold">{{ $svc['completed'] }} ({{ $svc['completion_rate'] }}%)</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="5">No service requests recorded during this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="callout-box">
        <strong>Fulfillment Disclosure:</strong> {{ $report['services']['financial_note'] }}
    </div>
    @endif

    {{-- SECTION 4: ORDERS AND DELIVERIES --}}
    @if(in_array($report['category'], ['all', 'orders']))
    <div class="section-title">4. Orders & Delivery Logistics</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18%;">Order Reference</th>
                <th style="width: 22%;">Customer / Date</th>
                <th style="width: 18%;">Source</th>
                <th class="text-right" style="width: 14%;">Total (RWF)</th>
                <th class="text-center" style="width: 14%;">Payment</th>
                <th class="text-center" style="width: 14%;">Fulfillment</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['orders']['recent_orders'] as $order)
                <tr>
                    <td class="fw-bold">{{ $order->reference }}</td>
                    <td>
                        <div>{{ $order->customer_name }}</div>
                        <div class="small text-muted">{{ $order->created_at->format('d M Y, H:i') }}</div>
                    </td>
                    <td>{{ match($order->source) { 'request_product' => 'Product Request', 'cart_checkout' => 'Cart Checkout', default => 'Storefront' } }}</td>
                    <td class="text-right fw-bold">{{ number_format($order->total, 0, '.', ',') }}</td>
                    <td class="text-center">
                        <span class="pill pill-{{ match($order->payment_status) { 'paid' => 'success', 'partially_paid' => 'warning', default => 'neutral' } }}">
                            {{ strtoupper($order->payment_status) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="pill pill-{{ match($order->fulfillment_status) { 'delivered', 'completed' => 'success', 'cancelled' => 'danger', 'confirmed', 'processing', 'ready_for_dispatch' => 'info', default => 'warning' } }}">
                            {{ strtoupper(str_replace('_', ' ', $order->fulfillment_status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">No orders placed within this date range.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- SECTION 5: INVENTORY AND STOCK MOVEMENT --}}
    @if(in_array($report['category'], ['all', 'inventory']))
    <div class="section-title">5. Inventory & Stock Movement Summary</div>
    <table class="summary-grid">
        <tr>
            <td class="summary-cell">
                <div class="summary-cell-title">Tracked Products</div>
                <div class="summary-cell-value">{{ $report['inventory']['tracked_count'] }}</div>
                <div class="summary-cell-sub">{{ $report['inventory']['healthy_count'] }} healthy stock items</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Stock Added</div>
                <div class="summary-cell-value text-success">+{{ number_format($report['inventory']['stock_added']) }}</div>
                <div class="summary-cell-sub">Additions & initial stock</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Stock Removed</div>
                <div class="summary-cell-value text-danger">-{{ number_format($report['inventory']['stock_removed']) }}</div>
                <div class="summary-cell-sub">Sales & removals</div>
            </td>
            <td class="summary-cell">
                <div class="summary-cell-title">Movements Recorded</div>
                <div class="summary-cell-value">{{ $report['inventory']['movements_total'] }}</div>
                <div class="summary-cell-sub">Approved stock actions</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 20%;">Timestamp</th>
                <th style="width: 30%;">Product / SKU</th>
                <th style="width: 15%;">Type</th>
                <th class="text-center" style="width: 10%;">Delta</th>
                <th class="text-center" style="width: 15%;">Stock Change</th>
                <th style="width: 10%;">Recorded By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['inventory']['recent_movements'] as $movement)
                <tr>
                    <td class="small">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                    <td>
                        <div class="fw-bold">{{ $movement->product?->product_name ?? 'Product' }}</div>
                        <div class="small text-muted">{{ $movement->product?->sku ?? '' }}</div>
                    </td>
                    <td>{{ Str::headline($movement->type) }}</td>
                    <td class="text-center fw-bold {{ $movement->quantity_delta >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $movement->quantity_delta > 0 ? '+' : '' }}{{ $movement->quantity_delta }}
                    </td>
                    <td class="text-center small">{{ $movement->stock_before }} → {{ $movement->stock_after }}</td>
                    <td class="small">{{ $movement->actor?->name ?? 'System' }}</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">No inventory movements recorded in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

    {{-- SECTION 6: VENDOR PERFORMANCE --}}
    @if(in_array($report['category'], ['all', 'vendors']) && $report['permissions']['can_view_vendors'])
    <div class="section-title">6. Vendor Fulfillment Performance</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Vendor Name</th>
                <th style="width: 18%;">District</th>
                <th class="text-center" style="width: 13%;">Assigned Items</th>
                <th class="text-center" style="width: 13%;">Accepted</th>
                <th class="text-center" style="width: 13%;">Delivered</th>
                <th class="text-right" style="width: 13%;">Value (RWF)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['vendors']['vendors'] as $row)
                <tr>
                    <td class="fw-bold">{{ $row['name'] }}</td>
                    <td>{{ $row['district'] }}</td>
                    <td class="text-center fw-bold">{{ $row['assigned'] }}</td>
                    <td class="text-center">{{ $row['accepted'] }}</td>
                    <td class="text-center text-success fw-bold">{{ $row['fulfilled'] }}</td>
                    <td class="text-right fw-bold">{{ number_format($row['total_value'], 0, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="empty-row"><td colspan="6">No vendor-allocated orders found for this period.</td></tr>
            @endforelse
        </tbody>
    </table>
    @endif

</body>
</html>

