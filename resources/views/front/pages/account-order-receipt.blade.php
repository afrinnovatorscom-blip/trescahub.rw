<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order Receipt {{ $order->reference }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 20mm 25mm;
        }

        body {
            margin: 0;
            color: #292d2b;
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.4;
        }

        * {
            font-family: "Times New Roman", Times, serif;
        }

        .receipt {
            width: 100%;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            padding: 0 0 14pt;
            border-bottom: 1.5px solid #b9c9bf;
            vertical-align: middle;
        }

        .brand-lockup {
            display: inline-block;
        }

        .brand-mark {
            display: inline-block;
            width: 29pt;
            height: 29pt;
            margin-right: 8pt;
            border-radius: 4pt;
            background: #168447;
            color: #fff;
            font-size: 18pt;
            font-weight: bold;
            line-height: 29pt;
            text-align: center;
            vertical-align: middle;
        }

        .brand {
            display: inline-block;
            font-size: 19pt;
            font-weight: bold;
            vertical-align: middle;
        }

        .brand-tresca {
            color: #168447;
        }

        .brand-hub {
            color: #292929;
            font-weight: normal;
        }

        .document-title {
            color: #252927;
            font-size: 19pt;
            font-weight: bold;
            letter-spacing: .3px;
            text-align: right;
            white-space: nowrap;
        }

        .document-reference {
            margin-top: 4pt;
            color: #59615d;
            font-size: 10pt;
            text-align: right;
        }

        .greeting {
            margin: 17pt 0 18pt;
            line-height: 1.5;
        }

        .greeting-name {
            margin-bottom: 3pt;
            font-size: 13pt;
            font-weight: bold;
        }

        .section-heading {
            margin: 0 0 6pt;
            color: #303633;
            font-size: 13pt;
            font-weight: bold;
        }

        .receipt-information {
            width: 100%;
            margin-bottom: 17pt;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .receipt-information td {
            width: 50%;
            padding: 7pt 10pt 7pt 0;
            border-bottom: 1px solid #e1e6e2;
            vertical-align: top;
        }

        .label {
            display: block;
            margin-bottom: 3pt;
            color: #5e6662;
            font-size: 9.5pt;
        }

        .value {
            color: #292d2b;
            font-size: 11pt;
            font-weight: bold;
        }

        .items-heading {
            page-break-after: avoid;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items th,
        .items td {
            padding: 8pt 7pt;
            border-bottom: 1px solid #d9dfdb;
            font-size: 10.5pt;
            line-height: 1.35;
            text-align: left;
            vertical-align: top;
        }

        .items th {
            border-top: 1.5px solid #aeb8b1;
            background: #f5f8f6;
            color: #454b48;
            font-weight: bold;
        }

        .items tbody tr {
            page-break-inside: avoid;
        }

        .items .product {
            width: 44%;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .items .quantity {
            width: 8%;
            text-align: center;
        }

        .items .unit-price,
        .items .subtotal {
            width: 24%;
            white-space: nowrap;
            text-align: right;
        }

        .totals {
            width: 100%;
            margin-top: 14pt;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .totals td {
            padding: 5pt 0 5pt 8pt;
            text-align: right;
        }

        .totals .total-row td {
            border-top: 1.5px solid #8e9b92;
            color: #292d2b;
            font-size: 13pt;
            font-weight: bold;
        }

        .not-recorded {
            color: #66706a;
            font-size: 9pt;
            font-style: italic;
        }

        .footer {
            position: fixed;
            right: 0;
            bottom: -13mm;
            left: 0;
            padding-top: 7pt;
            border-top: 1px solid #d9d9d9;
            color: #59615d;
            font-size: 9.5pt;
            line-height: 1.35;
            text-align: center;
        }

        .footer-thanks {
            margin-top: 2pt;
            color: #454b48;
        }

        .footer-website {
            margin-top: 1pt;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <table class="header">
            <tbody>
                <tr>
                    <td>
                        <div class="brand-lockup">
                            <span class="brand-mark">T</span>
                            <span class="brand"><span class="brand-tresca">TRESCA</span> <span class="brand-hub">Hub</span></span>
                        </div>
                    </td>
                    <td>
                        <div class="document-title">ORDER RECEIPT</div>
                        <div class="document-reference">Reference: {{ $order->reference }}</div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="greeting">
            <div class="greeting-name">Dear {{ $order->customer_name }},</div>
            <div>Thank you for placing your order with Tresca Hub. The order details are listed below.</div>
        </div>

        <div class="section-heading">Order and customer information</div>
        <table class="receipt-information">
            <tbody>
                <tr>
                    <td>
                        <span class="label">Order reference</span>
                        <span class="value">{{ $order->reference }}</span>
                    </td>
                    <td>
                        <span class="label">Order date</span>
                        <span class="value">{{ $order->created_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Customer name</span>
                        <span class="value">{{ $order->customer_name ?: 'Not recorded' }}</span>
                    </td>
                    <td>
                        <span class="label">Phone number</span>
                        <span class="value">{{ $order->phone ?: 'Not recorded' }}</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="label">Delivery address</span>
                        <span class="value">{{ $order->delivery_address ?: 'Not recorded' }}</span>
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-heading items-heading">Order details</div>
        <table class="items">
            <thead>
                <tr>
                    <th class="product">Product</th>
                    <th class="quantity">Qty</th>
                    <th class="unit-price">Unit price</th>
                    <th class="subtotal">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td class="product">
                            {{ $item->product_name }}
                            @if($item->variation_label && $item->variation_value)
                                ({{ $item->variation_label }}: {{ $item->variation_value }})
                            @endif
                        </td>
                        <td class="quantity">{{ $item->quantity }}</td>
                        <td class="unit-price">RWF {{ number_format((float) $item->unit_price, 2, '.', ',') }}</td>
                        <td class="subtotal">RWF {{ number_format((float) $item->line_total, 2, '.', ',') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tbody>
                <tr>
                    <td>Order subtotal</td>
                    <td>RWF {{ number_format((float) $order->subtotal, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Delivery fee</td>
                    <td class="not-recorded">Not separately recorded</td>
                </tr>
                <tr class="total-row">
                    <td>Total order amount</td>
                    <td>RWF {{ number_format((float) $order->total, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        <footer class="footer">
            <div>Generated on {{ $generatedAt->format('M j, Y g:i A') }}</div>
            <div class="footer-thanks">Thank you for choosing Tresca Hub.</div>
            @if($websiteUrl)
                <div class="footer-website">{{ $websiteUrl }}</div>
            @endif
        </footer>
    </div>
</body>
</html>
