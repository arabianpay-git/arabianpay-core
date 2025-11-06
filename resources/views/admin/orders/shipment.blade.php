@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Track Your Shipment</h1>

    <div class="bg-white shadow rounded p-6">
        <p><strong>Tracking Number:</strong> {{ $order->tracking }}</p>
        <p><strong>Order ID:</strong> #SF-{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}</p>
        <p><strong>Status:</strong> {{ ucfirst($order->delivery_status ?? 'pending') }}</p>
        <p><strong>Shipped On:</strong> {{ $order->shipped_at ? $order->shipped_at->format('d M, Y') : 'N/A' }}</p>
        <p><strong>Estimated Delivery:</strong> {{ $order->estimated_delivery ? $order->estimated_delivery->format('d M, Y') : 'TBD' }}</p>
    </div>
</div>
@endsection
