<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Milon\Barcode\Facades\DNS1DFacade;

class OrderController extends Controller
{
    private function getOrdersByStatus(string $status)
    {
        return Order::select(
            'id',
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at'
        )
            ->where('general_status', $status)
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);
    }

    private function getOrdersByShippingStatus(string $status)
    {
        return Order::select(
            'id',
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at'
        )
            ->where('delivery_status', $status)
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);
    }

    public function orders()
    {
        $orders = Order::select(
            'id',
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at'
        )
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);
        $type = "All";
        return view('admin.orders.index', compact('orders', 'type'));
    }



    public function orderDetails($id)
    {
        $order = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($id);

        $productDetails = collect(json_decode($order->product_details, true))->map(function ($item) {
            $product = Product::find($item['product_id']);

            // Check if attributes are not empty and contain price information
            if (!empty($item['attributes'])) {
                // Get the price from the first attribute if it has a price
                $price = $item['attributes'][0]['price'] ?? null;

                // If no price in attributes, use the product's unit price
                if (!$price) {
                    $price = $product->unit_price;
                }
            } else {
                // If no attributes, use the product's unit price
                $price = $product->unit_price;
            }

            // Return the processed product details
            return [
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => $price,
                'attributes' => $item['attributes'],
                'total' => $price * $item['quantity'], // Calculate total
            ];
        });

        return view('admin.orders.details', compact('order', 'productDetails'));
    }

    public function downloadInvoice($orderId)
    {
        // Fetch the order by ID
        $order = Order::find($orderId);

        $store = ShopSetting::where('user_id', Auth::id())->first();

        if (!$order) {
            return redirect()->back()->with('error', 'Order not found!');
        }

        // Decode product_details JSON
        $productDetails = collect(json_decode($order->product_details))->map(function ($item) {
            $product = Product::find($item->product_id);

            $attributes = $item->attributes ?? [];
            $attributePrice = collect($attributes)->sum('price');
            $total = $attributePrice * $item->quantity;

            return [
                'product' => $product,
                'quantity' => $item->quantity,
                'attributes' => $attributes,
                'price' => $attributePrice,
                'total' => $total,
            ];
        });

        // Calculate subtotal
        $subTotal = $productDetails->sum('total');

        // Pass data to view
        $data = [
            'order' => $order,
            'productDetails' => $productDetails,
            'subTotal' => $subTotal,
            'store' => $store
        ];

        // Load and generate the PDF
        $pdf = PDF::loadView('admin.orders.order_invoice', $data);

        // Return PDF download
        return $pdf->stream('invoice_' . $order->tracking . '.pdf');
        // return $pdf->download('invoice_' . $order->tracking . '.pdf');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'delivery_status' => 'nullable|in:pending,shipped,delivered,returned',
            'general_status' => 'nullable|in:processing,completed,cancelled,failed',
        ]);

        $order = Order::findOrFail($id);

        $order->delivery_status = $request->delivery_status;
        $order->general_status = $request->general_status;
        $order->save();

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }


    public function downloadShippingLabel($id)
    {
        try {
            // Add authorization check (e.g., policy)
            $order = Order::with('user')->findOrFail($id);
            $store = ShopSetting::where('user_id', Auth::id())->first();
            // Generate barcode
            $barcode = DNS1DFacade::getBarcodeSVG(
                $order->tracking,
                'C128',
                2,
                60,
                'black',
                false
            );

            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.orders.shipping_label', [
                'order' => $order,
                'barcode' => $barcode,
                'store' => $store
            ]);

            return $pdf->stream("shipping-label-{$order->tracking_number}.pdf");
        } catch (\Exception $e) {
            Log::error("Shipping label generation failed for Order #{$order->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate shipping label. Please try again.');
        }
    }

    public function trackShipment($tracking)
    {
        // Find order by its tracking number
        $order = Order::where('tracking', $tracking)->firstOrFail();

        // You can compute or fetch status history here if you have one.
        // For now, we just pass the order to a simple view.
        return view('admin.orders.shipment', compact('order'));
    }























    public function processing()
    {
        $orders = $this->getOrdersByStatus('processing');
        $type = "Pending";
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function confirmed()
    {
        $orders = $this->getOrdersByStatus('completed');
        $type = "Confirmed";
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function cancelled()
    {
        $orders = $this->getOrdersByStatus('cancelled');
        $type = "Cancelled";
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function failed()
    {
        $orders = $this->getOrdersByStatus('failed');
        $type = "Failed";
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function shippingOrder($status)
    {
        $orders = $this->getOrdersByShippingStatus($status);
        $type = ucfirst($status);
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function shippingOrders()
    {
        $orders = Order::select(
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at'
        )
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);
        $type = "All";
        return view('admin.orders.index', compact('orders', 'type'));
    }
}
