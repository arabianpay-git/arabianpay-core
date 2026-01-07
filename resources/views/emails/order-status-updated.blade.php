<!DOCTYPE html>
<html>

<head>
    <title>Order Status Update</title>
</head>

<body>
    <h2>Order Status Update</h2>
    <p>Dear {{ $customer_name }},</p>

    <p>Your order <strong>#{{ $order_id }}</strong> status has been updated:</p>

    <div style="background: #f5f5f5; padding: 15px; margin: 15px 0;">
        <p><strong>{{ $status_message }}</strong></p>
        <p>You can track your order here: <a href="{{ $tracking_url }}">{{ $tracking_url }}</a></p>
    </div>

    <p>Thank you for shopping with us!</p>

    <p>Best regards,<br>
        Your Store Team</p>
</body>

</html>
