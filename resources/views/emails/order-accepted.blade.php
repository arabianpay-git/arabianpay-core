<!DOCTYPE html>
<html>

<head>
    <title>Order Accepted</title>
</head>

<body>
    <h2>Order Accepted Successfully!</h2>
    <p>Dear {{ $customer_name }},</p>

    <p>Great news! Your order <strong>#{{ $order_id }}</strong> has been accepted and is now being processed.</p>

    <div style="background: #f5f5f5; padding: 15px; margin: 15px 0;">
        <h3>Order Details:</h3>
        <p><strong>Order ID:</strong> #{{ $order_id }}</p>
        <p><strong>Order Date:</strong> {{ $order_date }}</p>
        <p><strong>Invoice Number:</strong> {{ $invoice_number }}</p>
        <p><strong>Total Amount:</strong> ${{ number_format($order_total, 2) }}</p>
        <p><strong>Estimated Delivery:</strong> {{ $estimated_delivery_date }}</p>
    </div>

    <p>Your invoice is attached to this email for your records.</p>

    <p>We'll notify you when your order ships.</p>

    <p>Thank you for your purchase!<br>
        Your Store Team</p>
</body>

</html>
