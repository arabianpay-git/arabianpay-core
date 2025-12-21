<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\ShopSetting;
use App\Models\OrderActionLog;
use App\Models\Sanad;
use App\Services\FirebaseService;
use App\Services\NafithService;
use App\Traits\OtpSenderTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Detection\MobileDetect;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use OtpSenderTrait;

    public function orders()
    {
        $orders = $this->getOrdersByStatus();
        $type = 'All';
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function shippingOrder($status)
    {
        $orders = $this->getOrdersByStatus(null, $status);
        $type = ucfirst($status);
        return view('admin.orders.index', compact('orders', 'type'));
    }

    public function processing()
    {
        return $this->statusView('processing', 'Pending');
    }
    public function confirmed()
    {
        return $this->statusView('completed', 'Confirmed');
    }
    public function cancelled()
    {
        return $this->statusView('cancelled', 'Cancelled');
    }
    public function failed()
    {
        return $this->statusView('failed', 'Failed');
    }

    protected function statusView(string $status, string $type)
    {
        $orders = $this->getOrdersByStatus($status);

        return view('admin.orders.index', compact('orders', 'type'));
    }

    private function getOrdersByStatus(?string $status = null, $shippingStatus = null)
    {
        $user = Auth::user();

        $query = Order::with(['user', 'pickupPoint', 'assigned', 'schedulePayments'])
            ->when($user->user_type !== 'admin', fn($q) => $q->where('assigned_to', $user->id));

        if ($status) $query->where('general_status', $status);
        if ($shippingStatus) $query->where('delivery_status', $shippingStatus);

        $query->orderBy('created_at', 'desc');
        return $query->orderByRaw('assigned_to IS NULL DESC')->paginate(10);
    }

    public function orderDetails(int $id)
    {
        $user = Auth::user();

        $order = Order::with([
            'user',
            'seller',
            'pickupPoint',
            'assigned',
            'refund',
            'transactions'
        ])
            ->when($user->user_type !== 'admin', fn($q) => $q->where('assigned_to', $user->id))
            ->findOrFail($id);

        $productDetails = $this->mapProductDetails($order);
        $refundRequest = RefundRequest::where('order_id', $id)->first();
        $subTotal = $productDetails->sum('total');
        $totalQuantity = $productDetails->sum('quantity');
        $totalShippingFee = $order->shipping_cost;

        $schedulePayments = SchedulePayment::where('seller_id', $order->seller_id)
            ->where('order_id', $order->id)
            ->orderBy('instalment_number', 'asc')
            ->get();

        $actionLogs = OrderActionLog::with('seller', 'customer')
            ->where('order_id', $order->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.orders.details', compact(
            'order',
            'productDetails',
            'refundRequest',
            'subTotal',
            'totalQuantity',
            'totalShippingFee',
            'schedulePayments',
            'actionLogs'
        ));
    }

    /** Download invoice PDF */
    public function downloadInvoice(int $orderId)
    {
        $order = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
        $store = ShopSetting::firstWhere('user_id', $order->seller_id);
        $productDetails = $this->mapProductDetails($order);

        $pdf = Pdf::loadView('admin.orders.order_invoice', compact('order', 'store', 'productDetails'));
        return $pdf->stream("invoice_{$order->tracking}.pdf");
    }

    /** Download shipping label PDF */
    public function downloadShippingLabel(int $orderId)
    {
        try {
            $order = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
            $store = ShopSetting::firstWhere('user_id', $order->seller_id);
            $productDetails = $this->mapProductDetails($order);

            $pdf = Pdf::loadView('admin.orders.shipping_label', compact('order', 'store', 'productDetails'));
            return $pdf->stream("shipping-label_{$order->tracking}.pdf");
        } catch (\Exception $e) {
            Log::error("Shipping label error [Order {$orderId}]: {$e->getMessage()}");
            return back()->withError('Failed to generate shipping label. Please try again.');
        }
    }

    /** Update order status */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $request->validate([
                'delivery_status' => 'nullable|in:pending,shipped,delivered,returned',
                'general_status' => 'nullable|in:accepted,processing,cancelled,failed',
            ]);

            $order = Order::findOrFail($id);

            $deliveryFlow = ['pending', 'shipped', 'delivered', 'returned'];
            $generalFlow = ['processing', 'accepted', 'cancelled', 'failed'];

            $oldDeliveryStatus = $order->delivery_status;
            $oldGeneralStatus = $order->general_status;

            $newDeliveryStatus = $request->delivery_status ?? $oldDeliveryStatus;
            $newGeneralStatus = $request->general_status ?? $oldGeneralStatus;

            if (array_search($newDeliveryStatus, $deliveryFlow) < array_search($oldDeliveryStatus, $deliveryFlow)) {
                return back()->with('error', 'Invalid delivery status flow.');
            }

            if ($oldDeliveryStatus === 'delivered' && $newDeliveryStatus === 'returned') {
                return back()->with('error', 'Delivered order cannot be returned.');
            }

            if (array_search($newGeneralStatus, $generalFlow) < array_search($oldGeneralStatus, $generalFlow)) {
                return back()->with('error', 'Invalid general status flow.');
            }

            if ($oldGeneralStatus === 'accepted' && in_array($newGeneralStatus, ['cancelled', 'failed'])) {
                return back()->with('error', 'Accepted order cannot be cancelled or failed.');
            }

            DB::beginTransaction();

            if ($newDeliveryStatus === 'delivered' && $oldDeliveryStatus !== 'delivered') {
                $this->handleDeliveredStatus($order);
            }

            $order->update([
                'delivery_status' => $newDeliveryStatus,
                'general_status' => $newGeneralStatus,
            ]);

            // =========================
            //   Notification Payload
            // =========================
            $title = "Order #{$order->id} Status Updated";
            $description = "Your order status has been updated.";

            if ($oldDeliveryStatus !== $newDeliveryStatus) {
                $description .= " Delivery: {$newDeliveryStatus}.";
            }
            if ($oldGeneralStatus !== $newGeneralStatus) {
                $description .= " Status: {$newGeneralStatus}.";
            }

            $clickAction = url("/orders/{$order->id}");
            $mobileScreen = 'order_details';

            // =========================
            //   STORE IN DATABASE
            // =========================
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_status',
                'data' => json_encode([
                    'title' => $title,
                    'description' => $description,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                    'order_id' => $order->id,
                ]),
            ]);

            // =========================
            //   FIREBASE NOTIFICATION
            // =========================
            $firebaseService = app(FirebaseService::class);

            $firebaseService->sendCustomNotification(
                $order->user_id,
                $title,
                $description,
                [
                    'order_id' => $order->id,
                    'delivery_status' => $newDeliveryStatus,
                    'general_status' => $newGeneralStatus,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                ]
            );

            DB::commit();
            return back()->with('success', 'Order status updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order status update failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to update order status.');
        }
    }

    /** Accept order */
    public function acceptOrder(Request $request)
    {
        $this->authorizeUser();

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'invoice_number' => 'required|string|max:255',
            'estimated_delivery_date' => 'required|date',
            'invoice_file' => 'required|file|mimes:pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($request->order_id);
        $oldGeneralStatus = $order->general_status;

        DB::beginTransaction();
        try {
            if ($request->hasFile('invoice_file')) {
                $disk = 'public';
                $folder = 'media';
                $file = $request->file('invoice_file');

                $extension = strtolower($file->getClientOriginalExtension());
                $filename = Str::random(40) . '.' . $extension;
                $fullPath = "{$folder}/{$filename}";

                $file->storeAs($folder, $filename, $disk);
                $order->invoice_file = $fullPath;
            }

            $order->invoice_number = $request->invoice_number;
            $order->estimated_delivery_date = $request->estimated_delivery_date;
            $order->general_status = 'accepted';
            $order->save();

            $description = "Order accepted. Invoice #{$order->invoice_number} uploaded. Estimated delivery: {$order->estimated_delivery_date}. Status changed from '{$oldGeneralStatus}' to 'accepted'.";

            $this->logOrderAction(
                $order,
                'accept_order',
                $description,
                [
                    'invoice_number' => $order->invoice_number,
                    'estimated_delivery_date' => $order->estimated_delivery_date,
                ]
            );

            // Nafith SANAD
            // $nafithError = $this->createNafithSanad($order);
            // if ($nafithError) {
            //     DB::rollBack();
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => translate('Order updated, but SANAD creation failed: ') . $nafithError
            //     ], 422);
            // }

            $this->createSchedulePayments($order);

            // =========================
            //   Notification Payload
            // =========================
            $title = "Good News! Your Order #{$order->id} has been Accepted!";
            $body = "Invoice #{$order->invoice_number} uploaded. Estimated delivery: {$order->estimated_delivery_date}.";

            $clickAction = url("/orders/{$order->id}");
            $mobileScreen = 'order_details';

            // =========================
            //   STORE IN DATABASE
            // =========================
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_accepted',
                'data' => json_encode([
                    'title' => $title,
                    'description' => $body,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                ]),
            ]);

            // =========================
            // 📲 FIREBASE NOTIFICATION
            // =========================
            $firebaseService = app(FirebaseService::class);

            $firebaseService->sendCustomNotification(
                $order->user_id,
                $title,
                $body,
                [
                    'order_id' => $order->id,
                    'general_status' => 'accepted',
                    'invoice_number' => $order->invoice_number,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                ]
            );

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => translate('Order accepted successfully!')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to accept order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => translate('Failed to accept order. Please try again.')
            ], 500);
        }
    }

    /** Reject order */
    public function rejectOrder(Request $request)
    {
        $this->authorizeUser();

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rejection_reason' => 'required|string|max:500',
        ]);

        $order = Order::findOrFail($request->order_id);
        $oldGeneralStatus = $order->general_status;

        DB::beginTransaction();
        try {
            $order->general_status = 'rejected';
            $order->rejection_reason = $request->rejection_reason;
            $order->save();

            $description = "Order rejected. Status changed from '{$oldGeneralStatus}' to 'rejected'. Reason: {$order->rejection_reason}";

            $this->logOrderAction(
                $order,
                'reject_order',
                $description,
                ['rejection_reason' => $order->rejection_reason]
            );

            // =========================
            //   Notification Payload
            // =========================
            $title = "Your Order #{$order->id} has been Rejected";
            $body = "Reason: {$order->rejection_reason}. Please contact support for more details.";

            $clickAction = url("/orders/{$order->id}");
            $mobileScreen = 'order_details';

            // =========================
            //   STORE IN DATABASE
            // =========================
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_rejected',
                'data' => json_encode([
                    'title' => $title,
                    'description' => $body,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                    'order_id' => $order->id,
                    'rejection_reason' => $order->rejection_reason,
                ]),
            ]);

            // =========================
            //   FIREBASE NOTIFICATION
            // =========================
            $firebaseService = app(FirebaseService::class);

            $firebaseService->sendCustomNotification(
                $order->user_id,
                $title,
                $body,
                [
                    'order_id' => $order->id,
                    'general_status' => 'rejected',
                    'rejection_reason' => $order->rejection_reason,
                    'click_action' => $clickAction,
                    'mobile_screen' => $mobileScreen,
                ]
            );

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => translate('Order has been rejected successfully.')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to reject order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => translate('Failed to reject order. Please try again.')
            ], 500);
        }
    }

    /**
     * Create 3 installment payments for the accepted order.
     * @param Order $order
     * @return void
     */
    private function createSchedulePayments(Order $order): void
    {
        // Check if order already has 3 schedule payments
        $existingPayments = SchedulePayment::where('order_id', $order->id)->count();
        if ($existingPayments >= 3) {
            // Already created, skip
            return;
        }

        // Calculate the installment amount, rounded to 2 decimal places
        $installmentAmount = round($order->grand_total / 3, 2);
        $sellerId = $order->seller_id;
        $userId = $order->user_id;

        // Create 3 installments due at 30, 60, and 90 days
        for ($i = 1; $i <= 3; $i++) {
            SchedulePayment::create([
                'user_id' => $userId,
                'seller_id' => $sellerId,
                'order_id' => $order->id,
                'instalment_number' => $i,
                'due_date' => now()->addDays($i * 30)->toDateString(),
                'instalment_amount' => $installmentAmount,
                'principle_amount' => $installmentAmount, // Use installment amount as principle for simplicity
                'payment_method' => 'Card Payment',
                'payment_status' => 'pending',
                'assigned_to' => null, // Explicitly set to null
            ]);
        }

        $this->logOrderAction(
            $order,
            'schedule_payments_created',
            "3 installment payments created, each for {$installmentAmount} SAR. Due dates at 30, 60, 90 days.",
            ['instalment_count' => 3, 'first_due_date' => now()->addDays(30)->format('Y-m-d')]
        );
    }


    /**
     * Create Nafith SANAD if order meets criteria.
     * @param Order $order
     * @return string|null Error message string, or null on success.
     */
    private function createNafithSanad(Order $order): ?string
    {
        $user = $order->user;
        $nafithMaxAmount = env('NAFITH_MAX_AMOUNT', 10000);

        // Check initial criteria for eligibility
        if ($order->grand_total < $nafithMaxAmount) {
            return null;
        }

        if (Sanad::where('order_id', $order->id)->exists()) {
            $this->logOrderAction(
                $order,
                'nafith_sanad_skipped',
                'Nafith SANAD creation skipped: Sanad already exists for this order.',
                ['order_id' => $order->id]
            );
            return null;
        }

        // Check for missing required customer data
        if (empty($user->iqama) || empty($user->phone_number)) {
            return 'Missing Iqama or Phone number for the customer.';
        }

        // The NafithService should be bound in the container
        $nafithService = app(NafithService::class);

        $debtorData = [
            'national_id'  => (string) $user->iqama,
            'phone_number' => (string) $user->phone_number,
        ];

        $sanadItems = [
            [
                'due_type'     => 'date',
                'due_date'     => now()->addDays(90)->format('Y-m-d'),
                'total_value'  => $order->grand_total,
                'reference_id' => 'sanad_' . $order->id,
            ]
        ];

        $referenceId = 'order_' . $order->id . '_' . time();

        try {
            $response = $nafithService->createSingleSanad(
                $debtorData,
                $sanadItems,
                $referenceId,
                1
            );

            // Assuming Sanad model exists and response structure is correct
            Sanad::create([
                'id'           => $response['id'],
                'order_id'     => $order->id,
                'user_id'      => $user->id,
                'reference_id' => $response['reference_id'] ?? null,
                'status'       => $response['status'] ?? null,
                'total_value'  => $response['total_value'] ?? null,
                'currency'     => $response['currency'] ?? 'SAR',
                'issued_at'    => $response['issued_at'] ?? null,
                'approved_at'  => $response['approved_at'] ?? null,
                'raw_response' => $response,
            ]);

            $this->logOrderAction(
                $order,
                'nafith_sanad_created',
                "Nafith SANAD successfully created for {$order->grand_total} SAR, due " . $sanadItems[0]['due_date'],
                ['sanad_id' => $response['id'], 'nafith_response' => $response]
            );

            return null; // Success
        } catch (\Throwable $e) {
            Log::error("Nafith SANAD creation failed for order {$order->id}: {$e->getMessage()}", [
                'order_id'   => $order->id,
                'user_id'    => $user->id,
                'debtorData' => $debtorData,
                'sanadItems' => $sanadItems,
            ]);

            $this->logOrderAction(
                $order,
                'nafith_sanad_failed',
                "Failed to create Nafith SANAD: {$e->getMessage()}",
                ['error' => $e->getMessage(), 'debtor_data' => $debtorData]
            );

            return "API Error: {$e->getMessage()}";
        }
    }

    /** Handle delivered status */
    private function handleDeliveredStatus(Order $order): void
    {
        $otp = rand(100000, 999999);
        $order->delivery_otp = $otp;
        $order->save();

        $message = "Please submit this OTP on the ArabianPay mobile app to confirm your order delivery: {$otp}";
        $description = "Delivery OTP ({$otp}) generated and attempt to send to customer.";

        try {
            $sentSms = false;
            $sentEmail = false;

            if (!empty($order->user->phone_number)) {
                $this->sendSmsOtp($order->user->phone_number, $otp, $message);
                $sentSms = true;
            }
            if (!empty($order->user->email)) {
                $this->sendEmailOtp($order->user->email, $otp, 'Order Delivery OTP', $message);
                $sentEmail = true;
            }

            $description .= " SMS sent: " . ($sentSms ? 'Yes' : 'No') . ", Email sent: " . ($sentEmail ? 'Yes' : 'No') . ".";

            $this->logOrderAction(
                $order,
                'delivery_otp_sent',
                $description,
                [
                    'otp' => $otp,
                    'sms_attempt' => !empty($order->user->phone_number),
                    'email_attempt' => !empty($order->user->email),
                    'sms_success' => $sentSms,
                    'email_success' => $sentEmail,
                ]
            );
        } catch (\Throwable $e) {
            $description .= " Failed to send OTP.";
            Log::error('Failed to send delivery OTP', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            $this->logOrderAction(
                $order,
                'delivery_otp_send_failed',
                $description,
                [
                    'otp' => $otp,
                    'error_message' => $e->getMessage(),
                ]
            );
        }
    }

    /** Map product details */
    private function mapProductDetails(Order $order)
    {
        return collect(json_decode($order->product_details, true))
            ->map(fn($item) => $this->mapSingleProduct($item))
            ->filter();
    }

    private function mapSingleProduct(array $item)
    {
        $product = Product::find($item['product_id']);
        if (!$product) return null;
        $price = data_get($item, 'attributes.0.price', $product->unit_price);
        return [
            'product' => $product,
            'quantity' => $item['quantity'],
            'price' => $price,
            'attributes' => $item['attributes'] ?? [],
            'total' => $price * $item['quantity'],
        ];
    }

    /** Authorize admin or manager employee */
    private function authorizeUser(): void
    {
        $user = Auth::user();
        if (!($user->user_type === 'admin' || ($user->user_type === 'employee' && $user->is_manager))) {
            response()->json([
                'status' => 'error',
                'message' => translate('You do not have permission to perform this action.')
            ], 403)->send();
            exit;
        }
    }

    /**
     * Log an action related to the order.
     * @param Order $order The order model instance.
     * @param string $actionType The type of action (e.g., 'update_status', 'accept_order').
     * @param string|null $description A human-readable description of the action.
     * @param array $properties Additional structured data to store.
     * @return void
     */
    private function logOrderAction(Order $order, string $actionType, string $description = null, array $properties = []): void
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Device detection
        $detect = new MobileDetect();
        $device = $detect->isMobile() ? ($detect->isTablet() ? 'Tablet' : 'Mobile') : 'Desktop';

        // Simple platform/OS detection
        $platform = 'Unknown';
        if (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'Mac';
        } elseif (preg_match('/windows|win32/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $platform = 'iOS';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        }

        // Browser detection
        $browser = 'Unknown';
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent) && !preg_match('/Edge/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }

        $properties = array_merge($properties, [
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'device_type' => $device,
            'platform_os' => $platform,
            'browser_name' => $browser,
            'actor_user_id' => Auth::id(),
            'actor_user_type' => Auth::user()->user_type ?? 'Guest',
        ]);

        OrderActionLog::create([
            'seller_id' => $order->seller_id,
            'customer_id' => $order->user_id,
            'order_id' => $order->id,
            'action_type' => $actionType,
            'description' => $description ?? "{$actionType} performed on Order ID {$order->id}",
            'properties' => $properties,
        ]);
    }
}
