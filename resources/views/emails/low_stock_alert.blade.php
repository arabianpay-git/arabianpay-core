@extends('emails.layouts.app')

@section('content')
    <h2 style="color:#333; margin-bottom: 10px;">⚠️ Low Stock Alert ({{ $attemptNumber }} of 3)</h2>

    <p>Dear {{ $product->user->first_name ?? 'User' }},</p>

    <p>
        Your product
        <strong>{{ is_array($product->name) ? $product->name['en'] ?? reset($product->name) : $product->name }}</strong>
        is running low on stock. Please restock to avoid losing potential sales.
    </p>

    <table width="100%" cellpadding="8" cellspacing="0"
        style="background:#f8f9fa; border-radius:6px; margin:20px 0; border:1px solid #ddd;">
        <tr>
            <td><strong>Current Stock:</strong></td>
            <td>{{ $product->current_stock }}</td>
        </tr>
        <tr>
            <td><strong>Low Stock Threshold:</strong></td>
            <td>{{ $product->low_stock_quantity }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ partnerRoute('products.edit', ['product' => $product->id ?? null]) }}"
            style="display:inline-block; padding:10px 20px; background-color:#0d6efd; color:#fff;
                   text-decoration:none; border-radius:5px; font-weight:600;">
            View Product
        </a>
    </p>

    <p style="color:#6c757d; font-size:13px; margin-top:40px;">
        This is an automated reminder. You will receive up to 3 notifications unless restocked or unsubscribed.
    </p>
@endsection
