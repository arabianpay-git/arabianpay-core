<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #{{ $order->id }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            padding: 30px;
            max-width: 850px;
            margin: auto;
            background-color: #fff;
            border: 1px solid #ddd;
        }

        .header-section {
            margin-bottom: 20px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
            padding: 0 10px;
        }

        .company-logo img {
            height: 60px;
        }

        .company-details {
            font-size: 13px;
            line-height: 1.6;
            text-align: left;
        }

        .invoice-details {
            font-size: 13px;
            line-height: 1.6;
            text-align: right;
        }

        .invoice-label {
            font-weight: bold;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
            color: #222;
        }

        .header p {
            font-size: 16px;
            color: #777;
        }

        .order-details,
        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .order-details th,
        .order-details td,
        .summary td {
            padding: 10px 12px;
            border: 1px solid #ddd;
        }

        .order-details th {
            background-color: #f8f8f8;
            font-size: 14px;
            text-align: left;
        }

        .order-details td {
            font-size: 13px;
        }

        .summary td {
            font-size: 14px;
        }

        .summary .label {
            font-weight: bold;
            background-color: #f8f8f8;
            width: 70%;
        }

        .summary td:last-child {
            text-align: right;
        }

        .total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="container">

        {{-- Header Section --}}
        <div class="header-section">
            <table class="header-table">
                <tr>
                    <td class="company-logo" style="width: 50%;">
                        <img src="{{ public_path($store->logo) }}" alt="{{ $store->name }}">
                        <div class="company-details">
                            <div>{{ $store->name }}</div>
                            <div>{{ $store->address }}</div>
                            <div>Phone: {{ $store->phone_number }}</div>
                            <div>Email: {{ Auth::user()->email }}</div>
                        </div>
                    </td>
                    <td class="invoice-details" style="width: 50%;">
                        <div><strong>Invoice Number:</strong> #INV-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div><strong>Issue Date:</strong>
                            {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y') }}</div>
                        <div><strong>Status:</strong>
                            @if ($order->status === 'pending')
                                Pending
                            @elseif($order->status === 'shipped')
                                Shipped
                            @else
                                {{ ucfirst($order->general_status) }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Title --}}
        <div class="header">
            <h1>Invoice</h1>
            <p>Order #{{ strtoupper($order->tracking ?? '-') }}</p>
        </div>

        {{-- Products Table --}}
        <table class="order-details">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th> {{-- CHANGED: swapped columns --}}
                    <th>Quantity</th> {{-- CHANGED: swapped columns --}}
                    <th>Total</th> {{-- CHANGED: was “Price” --}}
                </tr>
            </thead>
            <tbody>
                @php
                    $subTotal = 0;
                    $totalQuantity = 0;
                @endphp

                {{-- CHANGED: loop over productDetails collection --}}
                @foreach ($productDetails as $item)
                    @php
                        $subTotal += $item['total'];
                        $totalQuantity += $item['quantity'];
                    @endphp
                    <tr>
                        <td>
                            <div class="font-semibold">{{ $item['product']->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-600">SKU: {{ $item['product']->sku ?? 'N/A' }}</div>

                            @if (!empty($item['attributes']))
                                @foreach ($item['attributes'] as $attribute)
                                    <div class="text-xs text-gray-600">
                                        {{ $attribute['attribute'] }}: {{ $attribute['value'] }}
                                    </div>
                                @endforeach
                            @endif
                        </td>
                        <td class="text-right">
                            <span class="icon-saudi_riyal"></span> {{ number_format($item['price'], 2) }}
                        </td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-right">
                            <span class="icon-saudi_riyal"></span> {{ number_format($item['total'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Summary --}}
        <table class="summary">
            <tr>
                <td class="label">Total Items</td>
                <td>{{ $totalQuantity }}</td>
            </tr>
            <tr>
                <td class="label">Subtotal</td>
                <td>SAR {{ number_format($subTotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Discount</td>
                <td>- SAR {{ number_format($order->coupon_discount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Shipping Fee</td>
                <td>
                    @if ($order->shipping_cost > 0)
                        SAR {{ number_format($order->shipping_cost, 2) }}
                    @else
                        Free delivery
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Total Tax</td>
                <td>SAR {{ $totalTax = calculate_order_tax($order) }}</td>
            </tr>
            <tr>
                <td class="label">Total Amount</td>
                <td>
                    SAR
                    {{ number_format($subTotal + $totalTax + ($order->shipping_cost ?? 0) - ($order->coupon_discount ?? 0), 2) }}
                </td>
            </tr>
        </table>

        {{-- Final Total --}}
        <div class="total">
            Total: SAR
            {{ number_format($subTotal + $totalTax + ($order->shipping_cost ?? 0) - ($order->coupon_discount ?? 0), 2) }}
        </div>
    </div>
</body>

</html>
