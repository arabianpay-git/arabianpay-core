<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Shipping Label - {{ $order->tracking }}</title>
    <style>
        @page {
            margin: 15px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .label-container {
            border: 2px solid #000;
            padding: 15px;
            page-break-inside: avoid;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .section-table th,
        .section-table td {
            padding: 5px;
            vertical-align: top;
            border: none;
        }

        .product-table {
            margin-top: 15px;
            border: 1px solid #dee2e6;
        }

        .product-table th,
        .product-table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
        }

        .product-table th {
            background: #f8f9fa;
        }

        .summary-box {
            margin-top: 15px;
            border: 1px solid #dee2e6;
            padding: 15px;
            font-size: 13px;
        }

        .summary-box td {
            padding: 5px;
        }

        .footer {
            margin-top: 15px;
            font-size: 10px;
            color: #6c757d;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="label-container">
        <div class="header">
            <h2 style="margin: 0;">SHIPPING LABEL</h2>
        </div>

        <table class="section-table" style="margin-bottom: 20px;">
            <tr>
                <td style="width: 50%; border-right: 1px solid #000;">
                    <h3 style="margin-top: 0; margin-bottom: 5px;">Shipper Information</h3>
                    @if ($store)
                        <p style="margin: 2px 0;">
                            {{ $store->name ?? '' }}<br>
                            {{ $store->address ?? '' }}<br>
                            Phone: {{ $store->phone_number ?? '' }}
                        </p>
                    @endif
                </td>
                <td style="width: 50%; padding-left: 10px;">
                    <h3 style="margin-top: 0; margin-bottom: 5px;">Recipient Information</h3>
                    <p style="margin: 2px 0;">
                        {{ $order->shipping_first_name }} {{ $order->shipping_last_name }}<br>
                        {{ $order->shipping_address_line1 }}<br>
                        @if ($order->shipping_address_line2)
                            {{ $order->shipping_address_line2 }}<br>
                        @endif
                        {{ $order->shipping_city }}, {{ $order->shipping_state }}
                        {{ $order->shipping_postal_code }}<br>
                        {{ $order->shipping_country }}
                    </p>
                </td>
            </tr>
        </table>

        <h3>Product Details</h3>
        <table class="product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Attributes</th>
                    <th>Price (SAR)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subTotal = 0;
                    $totalQuantity = 0;
                    $totalShippingFee = 0;
                    $totalDiscount = 0;
                @endphp
                @foreach ($productDetails as $item)
                    @php
                        $subTotal += $item['total'];
                        $totalQuantity += $item['quantity'];
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ $item['product']->name ?? 'N/A' }}</div>
                            <div style="font-size: 10px; color: #555;">SKU: {{ $item['product']->sku ?? 'N/A' }}</div>
                            @if (!empty($item['attributes']) && is_array($item['attributes']))
                                @foreach ($item['attributes'] as $attribute)
                                    <div style="font-size: 10px; color: #555;">{{ $attribute['attribute'] }}:
                                        {{ $attribute['value'] }}</div>
                                @endforeach
                            @endif
                            <div style="font-size: 10px; color: #555; margin-top: 5px;">
                                ↳ Shipping:
                                @if ($order->shipping_cost)
                                    SAR {{ $order->shipping_cost }}
                                @else
                                    Free delivery
                                @endif
                            </div>
                            <div style="font-size: 10px; color: #555;">
                                ↳ Store: {{ $order->user->business_name ?? 'Default Store' }}
                            </div>
                        </td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: center;">
                            @if (!empty($item['attributes']) && is_array($item['attributes']))
                                @foreach ($item['attributes'] as $attribute)
                                    <div style="font-size: 10px; color: #555;">{{ $attribute['attribute'] }}:
                                        {{ $attribute['value'] }}</div>
                                @endforeach
                            @else
                                N/A
                            @endif
                        </td>
                        <td style="text-align: right;">{{ number_format($item['price'], 2) }}</td>
                    </tr>
                @endforeach
                @php
                    if (!is_null($order->shipping_cost)) {
                        $totalShippingFee += $order->shipping_cost;
                    }
                    if (!is_null($order->coupon_discount)) {
                        $totalDiscount += $order->coupon_discount;
                    }
                @endphp
            </tbody>
        </table>

        <table class="summary-box">
            <tr>
                <td style="width: 50%;">
                    <strong>Total Items:</strong> {{ count(json_decode($order->product_details, true)) }}<br>
                    <strong>Total Quantity:</strong> {{ number_format($totalQuantity) }}
                </td>
                <td style="width: 50%; text-align: right;">
                    <strong>Discount:</strong> SAR {{ number_format($totalDiscount, 2) }}<br>
                    <strong>Shipping Cost:</strong> SAR {{ number_format($totalShippingFee, 2) }}<br>
                    <strong>Total Tax:</strong> SAR {{ $totalTax = calculate_order_tax($order) }}<br>
                    <strong>Grand Total:</strong> SAR
                    {{ number_format($subTotal + $totalTax + $totalShippingFee - $totalDiscount, 2) }}
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated by {{ $store->shop_name ?? 'Our Store' }} on {{ now()->format('M d, Y H:i') }}<br>
            {{ config('app.name') }} • {{ config('app.url') }}
        </div>
    </div>
</body>

</html>
