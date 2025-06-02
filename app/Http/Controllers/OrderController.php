<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Get paginated orders by general_status for current seller.
     */
    private function getOrdersByStatus(?string $status = null, $shippingStatus = null)
    {
        $user = currentUser();
        $query = Order::select([
            'id',
            'assigned_to',
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at'
        ])
            ->with(['user', 'pickupPoint', 'assigned'])
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            });
        if (!empty($status)) {
            $query->where('general_status', $status);
        }

        if (!empty($shippingStatus)) {
            $query->where('delivery_status', $shippingStatus);
        }

        return $query->orderByRaw('assigned_to IS NULL DESC')->paginate(10);
    }

    /** Show all orders */
    public function orders()
    {
        $orders = $this->getOrdersByStatus('');
        $type   = 'All';

        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function shippingOrders()
    {
        $orders = $this->getOrdersByStatus('');
        $type   =  'All';

        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function shippingOrder($status)
    {
        $orders = $this->getOrdersByStatus(null, $status);
        $type   =  ucfirst($status);

        return view('admin.orders.index', compact('orders', 'type'));
    }

    /** Show orders by status tabs */
    public function processing()
    {
        return $this->statusView('processing', 'Pending');
    }
    public function confirmed()
    {
        return $this->statusView('completed',  'Confirmed');
    }
    public function cancelled()
    {
        return $this->statusView('cancelled',  'Cancelled');
    }
    public function failed()
    {
        return $this->statusView('failed',     'Failed');
    }

    /** Helper for status-based index views */
    protected function statusView(string $status, string $type)
    {
        $orders = $this->getOrdersByStatus($status);
        return view('admin.orders.index', compact('orders', 'type'));
    }

    /** Show single order details */
    public function orderDetails(int $id)
    {
        $user = currentUser();

        // Fetch order without access filter
        $order = Order::with(['user', 'seller', 'pickupPoint', 'assigned'])
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })->find($id);

        $productDetails = $this->mapProductDetails($order);

        return view('admin.orders.details', compact('order', 'productDetails'));
    }

    /** Download invoice PDF */
    public function downloadInvoice(int $orderId)
    {
        $order          = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
        $store          = ShopSetting::firstWhere('user_id', $order->seller_id);
        $productDetails = $this->mapProductDetails($order);

        $pdf = Pdf::loadView('admin.orders.order_invoice', compact(
            'order',
            'store',
            'productDetails'
        ));

        return $pdf->stream("invoice_{$order->tracking}.pdf");
    }

    /** Download shipping label PDF */
    public function downloadShippingLabel(int $orderId)
    {
        try {
            $order          = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
            $store          = ShopSetting::firstWhere('user_id', $order->seller_id);
            $productDetails = $this->mapProductDetails($order);

            $pdf = Pdf::loadView('admin.orders.shipping_label', compact(
                'order',
                'store',
                'productDetails',
            ));

            return $pdf->stream("shipping-label_{$order->tracking}.pdf");
        } catch (\Exception $e) {
            Log::error("Shipping label error [Order {$orderId}]: {$e->getMessage()}");
            return back()->withError('Failed to generate shipping label. Please try again.');
        }
    }

    /** Update order statuses */
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'delivery_status' => 'nullable|in:pending,shipped,delivered,returned',
            'general_status'  => 'nullable|in:processing,completed,cancelled,failed',
        ]);

        $order = Order::findOrFail($id);
        $order->update($request->only(['delivery_status', 'general_status']));

        // Log the status update
        Auth::user()->logModelAction(
            event: 'update_status',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated order status for order ID: {$order->id}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        return back()->withSuccess('Order status updated successfully.');
    }

    /**
     * Decode JSON product_details and map into usable array.
     */
    private function mapProductDetails(Order $order): \Illuminate\Support\Collection
    {
        return collect(json_decode($order->product_details, true))
            ->map(function (array $item) {
                $product = Product::find($item['product_id']);

                if (!$product) {
                    return null;
                }

                $price = data_get($item, 'attributes.0.price', $product->unit_price);
                $quantity = $item['quantity'];

                return [
                    'product'    => $product,
                    'quantity'   => $quantity,
                    'price'      => $price,
                    'attributes' => $item['attributes'] ?? [],
                    'total'      => $price * $quantity,
                ];
            })
            ->filter(); // Remove any nulls if product was not found
    }
}
