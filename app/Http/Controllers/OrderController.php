<?php

namespace App\Http\Controllers;

use App\Mail\OrderAccepted;
use App\Mail\OrderStatusUpdated;
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
use App\Services\AuditTrailService;
use App\Traits\OtpSenderTrait;
use App\Traits\SmsTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Detection\MobileDetect;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    use OtpSenderTrait, SmsTrait;

    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function orders()
    {
        try {
            $orders = $this->getOrdersByStatus();
            $type = 'All';

            // Log view operation
            $this->auditTrailService->logViewOperation(
                'orders_list_view',
                'Order',
                'Viewed all orders list',
                [
                    'total_orders' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'user_type' => Auth::user()->user_type
                ]
            );

            return view('admin.orders.index', compact('orders', 'type'));
        } catch (\Exception $e) {
            Log::error('Failed to load orders list', ['error' => $e->getMessage()]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'orders_list_failed',
                'entity_type' => 'Order',
                'action_summary' => 'Failed to load orders list',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load orders. Please try again.');
        }
    }

    public function shippingOrder($status)
    {
        try {
            $orders = $this->getOrdersByStatus(null, $status);
            $type = ucfirst($status);

            // Log view operation with filter
            $this->auditTrailService->logViewOperation(
                'shipping_orders_view',
                'Order',
                'Viewed orders by shipping status: ' . $status,
                [
                    'shipping_status' => $status,
                    'total_orders' => $orders->total(),
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage()
                ]
            );

            return view('admin.orders.index', compact('orders', 'type'));
        } catch (\Exception $e) {
            Log::error('Failed to load shipping orders', [
                'error' => $e->getMessage(),
                'status' => $status
            ]);

            return redirect()->back()->with('error', 'Failed to load shipping orders.');
        }
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
        try {
            $orders = $this->getOrdersByStatus($status);

            // Log status-based view
            $this->auditTrailService->logViewOperation(
                'status_orders_view',
                'Order',
                'Viewed orders with status: ' . $status,
                [
                    'status' => $status,
                    'total_orders' => $orders->total(),
                    'current_page' => $orders->currentPage()
                ]
            );

            return view('admin.orders.index', compact('orders', 'type'));
        } catch (\Exception $e) {
            Log::error('Failed to load orders by status', [
                'error' => $e->getMessage(),
                'status' => $status
            ]);

            return redirect()->back()->with('error', 'Failed to load orders.');
        }
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
        try {
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

            // Log order details view with justification (contains customer PII)
            $justificationData = $this->auditTrailService->withJustification(
                'Order details view required for customer service, fulfillment, and financial processing',
                'legitimate_interest',
                ['customer_id', 'customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'order_items']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'data_access',
                'event_type' => 'order_details_view',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Viewed detailed information for order #' . $order->id,
                'properties' => [
                    'order_id' => $order->id,
                    'tracking_number' => $order->tracking,
                    'customer_id' => $order->user_id,
                    'order_status' => $order->general_status,
                    'delivery_status' => $order->delivery_status,
                    'total_amount' => $order->grand_total,
                    'product_count' => $productDetails->count(),
                    'has_refund_request' => !is_null($refundRequest),
                    'payment_schedule_count' => $schedulePayments->count(),
                    'viewed_by' => $user->id,
                    'viewed_by_type' => $user->user_type
                ]
            ], $justificationData));

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
        } catch (\Exception $e) {
            Log::error('Failed to load order details', [
                'error' => $e->getMessage(),
                'order_id' => $id,
                'user_id' => Auth::id()
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'order_details_failed',
                'entity_type' => 'Order',
                'entity_id' => $id,
                'action_summary' => 'Failed to load order details',
                'properties' => [
                    'error' => $e->getMessage(),
                    'requested_order_id' => $id,
                    'user_id' => Auth::id()
                ]
            ]);

            return redirect()->back()->with('error', 'Failed to load order details. Please try again.');
        }
    }

    /** Download invoice PDF */
    public function downloadInvoice(int $orderId)
    {
        try {
            $order = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
            $store = ShopSetting::firstWhere('user_id', $order->seller_id);
            $productDetails = $this->mapProductDetails($order);

            // Log invoice download
            $justificationData = $this->auditTrailService->withJustification(
                'Invoice download required for accounting, record keeping, and customer documentation',
                'legal_obligation',
                ['customer_details', 'order_details', 'billing_information', 'payment_details']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'document_access',
                'event_type' => 'invoice_download',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Downloaded invoice PDF for order #' . $order->id,
                'properties' => [
                    'order_id' => $order->id,
                    'invoice_number' => $order->invoice_number,
                    'customer_id' => $order->user_id,
                    'total_amount' => $order->grand_total,
                    'currency' => 'SAR',
                    'download_timestamp' => now()->toISOString(),
                    'downloaded_by' => Auth::id()
                ]
            ], $justificationData));

            $pdf = Pdf::loadView('admin.orders.order_invoice', compact('order', 'store', 'productDetails'));
            return $pdf->stream("invoice_{$order->tracking}.pdf");
        } catch (\Exception $e) {
            Log::error("Invoice download error [Order {$orderId}]: {$e->getMessage()}");

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'invoice_download_failed',
                'entity_type' => 'Order',
                'entity_id' => $orderId,
                'action_summary' => 'Failed to download invoice PDF',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $orderId
                ]
            ]);

            return back()->withError('Failed to generate invoice. Please try again.');
        }
    }

    /** Download shipping label PDF */
    public function downloadShippingLabel(int $orderId)
    {
        try {
            $order = Order::with(['user', 'seller', 'pickupPoint'])->findOrFail($orderId);
            $store = ShopSetting::firstWhere('user_id', $order->seller_id);
            $productDetails = $this->mapProductDetails($order);

            // Log shipping label download
            $justificationData = $this->auditTrailService->withJustification(
                'Shipping label download required for fulfillment and logistics operations',
                'legitimate_interest',
                ['shipping_address', 'customer_name', 'order_details']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'document_access',
                'event_type' => 'shipping_label_download',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Downloaded shipping label for order #' . $order->id,
                'properties' => [
                    'order_id' => $order->id,
                    'shipping_address' => substr($order->shipping_address, 0, 100) . '...', // Truncated for privacy
                    'delivery_status' => $order->delivery_status,
                    'download_timestamp' => now()->toISOString(),
                    'downloaded_by' => Auth::id()
                ]
            ], $justificationData));

            $pdf = Pdf::loadView('admin.orders.shipping_label', compact('order', 'store', 'productDetails'));
            return $pdf->stream("shipping-label_{$order->tracking}.pdf");
        } catch (\Exception $e) {
            Log::error("Shipping label error [Order {$orderId}]: {$e->getMessage()}");

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'shipping_label_failed',
                'entity_type' => 'Order',
                'entity_id' => $orderId,
                'action_summary' => 'Failed to generate shipping label',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $orderId
                ]
            ]);

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

            // Capture before state for audit
            $beforeState = $order->toArray();

            $deliveryFlow = ['pending', 'shipped', 'delivered', 'returned'];
            $generalFlow = ['processing', 'accepted', 'cancelled', 'failed'];

            $oldDeliveryStatus = $order->delivery_status;
            $oldGeneralStatus = $order->general_status;

            $newDeliveryStatus = $request->delivery_status ?? $oldDeliveryStatus;
            $newGeneralStatus = $request->general_status ?? $oldGeneralStatus;

            if (array_search($newDeliveryStatus, $deliveryFlow) < array_search($oldDeliveryStatus, $deliveryFlow)) {
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'invalid_status_flow',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Attempted invalid delivery status flow',
                    'properties' => [
                        'old_delivery_status' => $oldDeliveryStatus,
                        'new_delivery_status' => $newDeliveryStatus,
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id
                    ]
                ]);

                return back()->with('error', 'Invalid delivery status flow.');
            }

            if ($oldDeliveryStatus === 'delivered' && $newDeliveryStatus === 'returned') {
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'invalid_delivered_return',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Attempted to return already delivered order',
                    'properties' => [
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id,
                        'current_status' => 'delivered',
                        'attempted_status' => 'returned'
                    ]
                ]);

                return back()->with('error', 'Delivered order cannot be returned.');
            }

            if (array_search($newGeneralStatus, $generalFlow) < array_search($oldGeneralStatus, $generalFlow)) {
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'invalid_general_status_flow',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Attempted invalid general status flow',
                    'properties' => [
                        'old_general_status' => $oldGeneralStatus,
                        'new_general_status' => $newGeneralStatus,
                        'order_id' => $order->id
                    ]
                ]);

                return back()->with('error', 'Invalid general status flow.');
            }

            if ($oldGeneralStatus === 'accepted' && in_array($newGeneralStatus, ['cancelled', 'failed'])) {
                $this->auditTrailService->log([
                    'event_category' => 'validation_errors',
                    'event_type' => 'invalid_accepted_cancellation',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Attempted to cancel/fail already accepted order',
                    'properties' => [
                        'order_id' => $order->id,
                        'current_status' => 'accepted',
                        'attempted_status' => $newGeneralStatus
                    ]
                ]);

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

            // Log the status update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Order status update required for operational workflow, customer communication, and fulfillment tracking',
                'legitimate_interest',
                ['order_status', 'delivery_status', 'customer_id']
            );

            $this->auditTrailService->logUpdated(
                $order,
                $beforeState,
                'Updated order #' . $order->id . ' status',
                array_merge([
                    'event_category' => 'order_operations',
                    'event_type' => 'status_update',
                    'entity_type' => 'Order',
                    'properties' => [
                        'old_delivery_status' => $oldDeliveryStatus,
                        'new_delivery_status' => $newDeliveryStatus,
                        'old_general_status' => $oldGeneralStatus,
                        'new_general_status' => $newGeneralStatus,
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id,
                        'updated_by' => Auth::id(),
                        'updated_by_type' => Auth::user()->user_type,
                        'changes_made' => $this->getOrderChangedFields($beforeState, $order->toArray())
                    ]
                ], $justificationData)
            );

            $descriptionParts = [];
            if ($oldDeliveryStatus !== $newDeliveryStatus) {
                $descriptionParts[] = "Delivery status changed from '{$oldDeliveryStatus}' to '{$newDeliveryStatus}'";
            }
            if ($oldGeneralStatus !== $newGeneralStatus) {
                $descriptionParts[] = "General status changed from '{$oldGeneralStatus}' to '{$newGeneralStatus}'";
            }
            $description = implode(' and ', $descriptionParts) ?: 'Order status updated.';

            $this->logOrderAction(
                $order,
                'update_status',
                $description,
                compact('oldDeliveryStatus', 'newDeliveryStatus', 'oldGeneralStatus', 'newGeneralStatus')
            );

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

            // =========================
            //   SMS & EMAIL NOTIFICATIONS
            // =========================
            $user = $order->user ?? null;

            if ($user) {
                // Prepare status-specific messages
                $statusMessages = [
                    'pending' => 'Your order is confirmed and awaiting processing.',
                    'processing' => 'Your order is being prepared for shipment.',
                    'accepted' => 'Your order has been accepted and is now being processed.',
                    'shipped' => 'Your order has been shipped and is on its way.',
                    'delivered' => 'Your order has been delivered successfully.',
                    'cancelled' => 'Your order has been cancelled.',
                    'failed' => 'There was an issue processing your order.',
                    'returned' => 'Your order has been returned.',
                ];

                $statusUpdateText = '';
                if ($oldDeliveryStatus !== $newDeliveryStatus) {
                    $statusUpdateText .= "Delivery Status: " . ucfirst($newDeliveryStatus) . ". ";
                }
                if ($oldGeneralStatus !== $newGeneralStatus) {
                    $statusUpdateText .= "Order Status: " . ucfirst($newGeneralStatus) . ". ";
                }

                // SMS Notification
                if (!empty($user->phone_number)) {
                    try {
                        $smsMessage = "Order #{$order->id}: " . trim($statusUpdateText) . " Track at: " . url('/orders/track/' . $order->id);
                        $smsSent = $this->sendOrderSms($user->phone_number, $smsMessage);

                        // Log SMS sending in audit trail
                        if ($smsSent) {
                            $this->auditTrailService->log([
                                'event_category' => 'notification_events',
                                'event_type' => 'sms_notification_sent',
                                'entity_type' => 'Order',
                                'entity_id' => $order->id,
                                'action_summary' => 'Sent SMS notification for order status update',
                                'properties' => [
                                    'order_id' => $order->id,
                                    'customer_id' => $order->user_id,
                                    'phone_number' => $user->phone_number,
                                    'message' => $smsMessage,
                                    'sms_status' => 'sent'
                                ]
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error("SMS send failed for order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'sms_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send SMS notification',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'phone_number' => $user->phone_number,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }

                // Email Notification
                if (!empty($user->email)) {
                    try {
                        $emailData = [
                            'subject' => $title,
                            'order_id' => $order->id,
                            'delivery_status' => ucfirst($newDeliveryStatus),
                            'general_status' => ucfirst($newGeneralStatus),
                            'status_message' => trim($statusUpdateText),
                            'tracking_url' => url('/orders/track/' . $order->id),
                            'customer_name' => $user->name ?? 'Customer',
                        ];

                        $this->sendOrderStatusEmail($user->email, $emailData);

                        // Log email sending in audit trail
                        $this->auditTrailService->log([
                            'event_category' => 'notification_events',
                            'event_type' => 'email_notification_sent',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Sent email notification for order status update',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'subject' => $title,
                                'email_status' => 'sent'
                            ]
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("Email send failed for order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'email_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send email notification',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }
            }

            DB::commit();

            // Log notification sent
            $this->auditTrailService->log([
                'event_category' => 'notification_events',
                'event_type' => 'status_notification_sent',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Sent status update notification to customer',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id,
                    'notification_type' => 'order_status',
                    'delivery_status' => $newDeliveryStatus,
                    'general_status' => $newGeneralStatus,
                    'channels' => ['firebase', 'database', 'sms', 'email']
                ]
            ]);

            return back()->with('success', 'Order status updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Order status update failed', ['error' => $e->getMessage()]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'status_update_failed',
                'entity_type' => 'Order',
                'entity_id' => $id,
                'action_summary' => 'Failed to update order status',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $id,
                    'attempted_by' => Auth::id(),
                    'delivery_status' => $request->delivery_status ?? 'not_provided',
                    'general_status' => $request->general_status ?? 'not_provided'
                ]
            ]);

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
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'order_accept_validation_failed',
                'entity_type' => 'Order',
                'entity_id' => $request->order_id,
                'action_summary' => 'Order acceptance failed validation',
                'properties' => [
                    'validation_errors' => $validator->errors()->toArray(),
                    'order_id' => $request->order_id,
                    'attempted_by' => Auth::id()
                ]
            ]);

            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($request->order_id);
        $oldGeneralStatus = $order->general_status;

        // Capture before state for audit
        $beforeState = $order->toArray();

        DB::beginTransaction();
        try {
            $invoicePath = null;
            if ($request->hasFile('invoice_file')) {
                $disk = 'public';
                $folder = 'media';
                $file = $request->file('invoice_file');

                $extension = strtolower($file->getClientOriginalExtension());
                $filename = Str::random(40) . '.' . $extension;
                $fullPath = "{$folder}/{$filename}";

                $file->storeAs($folder, $filename, $disk);
                $order->invoice_file = $fullPath;
                $invoicePath = storage_path('app/public/' . $fullPath);
            }

            $order->invoice_number = $request->invoice_number;
            $order->estimated_delivery_date = $request->estimated_delivery_date;
            $order->general_status = 'accepted';
            $order->save();

            // Log order acceptance with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Order acceptance required for processing fulfillment, invoicing, and customer confirmation',
                'legitimate_interest',
                ['invoice_number', 'invoice_file', 'customer_details', 'order_details']
            );

            $this->auditTrailService->logUpdated(
                $order,
                $beforeState,
                'Accepted order #' . $order->id . ' with invoice #' . $order->invoice_number,
                array_merge([
                    'event_category' => 'order_operations',
                    'event_type' => 'order_accepted',
                    'entity_type' => 'Order',
                    'properties' => [
                        'old_status' => $oldGeneralStatus,
                        'new_status' => 'accepted',
                        'invoice_number' => $order->invoice_number,
                        'estimated_delivery_date' => $order->estimated_delivery_date,
                        'invoice_file_path' => $order->invoice_file,
                        'accepted_by' => Auth::id(),
                        'changes_made' => $this->getOrderChangedFields($beforeState, $order->toArray())
                    ]
                ], $justificationData)
            );

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

            // =========================
            //   SMS & EMAIL NOTIFICATIONS
            // =========================
            $user = $order->user ?? null;

            if ($user) {
                // SMS Notification
                if (!empty($user->phone_number)) {
                    try {
                        $smsMessage = "Great news! Order #{$order->id} has been accepted. Invoice #{$order->invoice_number}. Estimated delivery: {$order->estimated_delivery_date}. View details: " . url('/orders/' . $order->id);
                        $smsSent = $this->sendOrderSms($user->phone_number, $smsMessage);

                        if ($smsSent) {
                            $this->auditTrailService->log([
                                'event_category' => 'notification_events',
                                'event_type' => 'sms_notification_sent',
                                'entity_type' => 'Order',
                                'entity_id' => $order->id,
                                'action_summary' => 'Sent SMS notification for order acceptance',
                                'properties' => [
                                    'order_id' => $order->id,
                                    'customer_id' => $order->user_id,
                                    'phone_number' => $user->phone_number,
                                    'message' => $smsMessage,
                                    'sms_status' => 'sent'
                                ]
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error("SMS send failed for accepted order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'sms_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send SMS notification for order acceptance',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'phone_number' => $user->phone_number,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }

                // Email Notification with Invoice Attachment
                if (!empty($user->email)) {
                    try {
                        $emailData = [
                            'subject' => "Order #{$order->id} Accepted - Invoice Attached",
                            'order_id' => $order->id,
                            'invoice_number' => $order->invoice_number,
                            'estimated_delivery_date' => $order->estimated_delivery_date,
                            'customer_name' => $user->name ?? 'Customer',
                            'order_total' => $order->total_amount ?? 0,
                            'order_date' => $order->created_at->format('d/m/Y'),
                            'attachment_path' => $invoicePath,
                            'attachment_name' => "Invoice_{$order->invoice_number}.pdf",
                        ];

                        $this->sendOrderAcceptedEmail($user->email, $emailData);

                        $this->auditTrailService->log([
                            'event_category' => 'notification_events',
                            'event_type' => 'email_notification_sent',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Sent email notification with invoice attachment for order acceptance',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'subject' => $emailData['subject'],
                                'invoice_number' => $order->invoice_number,
                                'attachment_sent' => $invoicePath !== null,
                                'email_status' => 'sent'
                            ]
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("Email send failed for accepted order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'email_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send email notification for order acceptance',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }
            }

            DB::commit();

            // Log successful acceptance
            $this->auditTrailService->log([
                'event_category' => 'order_operations',
                'event_type' => 'order_accept_completed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Order acceptance process completed successfully',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id,
                    'invoice_number' => $order->invoice_number,
                    'schedule_payments_created' => true,
                    'notification_channels' => ['firebase', 'database', 'sms', 'email'],
                    'email_with_invoice' => $invoicePath !== null
                ]
            ]);

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

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'order_accept_failed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Failed to accept order',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'attempted_by' => Auth::id(),
                    'invoice_number' => $request->invoice_number
                ]
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

        // Capture before state for audit
        $beforeState = $order->toArray();

        DB::beginTransaction();
        try {
            $order->general_status = 'rejected';
            $order->rejection_reason = $request->rejection_reason;
            $order->save();

            // Log order rejection with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Order rejection required due to inventory issues, policy compliance, or customer request',
                'legitimate_interest',
                ['rejection_reason', 'customer_details', 'order_details']
            );

            $this->auditTrailService->logUpdated(
                $order,
                $beforeState,
                'Rejected order #' . $order->id,
                array_merge([
                    'event_category' => 'order_operations',
                    'event_type' => 'order_rejected',
                    'entity_type' => 'Order',
                    'properties' => [
                        'old_status' => $oldGeneralStatus,
                        'new_status' => 'rejected',
                        'rejection_reason' => $order->rejection_reason,
                        'rejected_by' => Auth::id(),
                        'rejected_by_type' => Auth::user()->user_type,
                        'changes_made' => $this->getOrderChangedFields($beforeState, $order->toArray())
                    ]
                ], $justificationData)
            );

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

            // =========================
            //   SMS & EMAIL NOTIFICATIONS
            // =========================
            $user = $order->user ?? null;

            if ($user) {
                // SMS Notification
                if (!empty($user->phone_number)) {
                    try {
                        $smsMessage = "Update: Order #{$order->id} has been rejected. Reason: {$order->rejection_reason}. Contact support: " . (config('app.support_phone') ?? config('app.support_email'));
                        $smsSent = $this->sendOrderSms($user->phone_number, $smsMessage);

                        if ($smsSent) {
                            $this->auditTrailService->log([
                                'event_category' => 'notification_events',
                                'event_type' => 'sms_notification_sent',
                                'entity_type' => 'Order',
                                'entity_id' => $order->id,
                                'action_summary' => 'Sent SMS notification for order rejection',
                                'properties' => [
                                    'order_id' => $order->id,
                                    'customer_id' => $order->user_id,
                                    'phone_number' => $user->phone_number,
                                    'message' => $smsMessage,
                                    'sms_status' => 'sent'
                                ]
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::error("SMS send failed for rejected order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'sms_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send SMS notification for order rejection',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'phone_number' => $user->phone_number,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }

                // Email Notification
                if (!empty($user->email)) {
                    try {
                        $emailData = [
                            'subject' => $title,
                            'order_id' => $order->id,
                            'rejection_reason' => $order->rejection_reason,
                            'customer_name' => $user->name ?? 'Customer',
                            'support_contact' => config('app.support_phone') ?? config('app.support_email'),
                            'order_date' => $order->created_at->format('d/m/Y'),
                        ];

                        $this->sendOrderStatusEmail($user->email, $emailData);

                        $this->auditTrailService->log([
                            'event_category' => 'notification_events',
                            'event_type' => 'email_notification_sent',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Sent email notification for order rejection',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'subject' => $title,
                                'rejection_reason' => $order->rejection_reason,
                                'email_status' => 'sent'
                            ]
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("Email send failed for rejected order {$order->id}: " . $e->getMessage());
                        $this->auditTrailService->log([
                            'event_category' => 'error_events',
                            'event_type' => 'email_notification_failed',
                            'entity_type' => 'Order',
                            'entity_id' => $order->id,
                            'action_summary' => 'Failed to send email notification for order rejection',
                            'properties' => [
                                'order_id' => $order->id,
                                'customer_id' => $order->user_id,
                                'email' => $user->email,
                                'error' => $e->getMessage()
                            ]
                        ]);
                    }
                }
            }

            DB::commit();

            // Log rejection completion
            $this->auditTrailService->log([
                'event_category' => 'order_operations',
                'event_type' => 'order_reject_completed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Order rejection process completed',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id,
                    'rejection_reason' => $order->rejection_reason,
                    'notification_channels' => ['firebase', 'database', 'sms', 'email']
                ]
            ]);

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

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'order_reject_failed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Failed to reject order',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'rejection_reason' => $request->rejection_reason
                ]
            ]);

            return response()->json([
                'status' => 'error',
                'message' => translate('Failed to reject order. Please try again.')
            ], 500);
        }
    }

    /**
     * Send order status email
     */
    protected function sendOrderStatusEmail($email, $data)
    {
        $mailer = new OrderStatusUpdated($data);
        Mail::to($email)->send($mailer);
    }

    /**
     * Send order accepted email with invoice attachment
     */
    protected function sendOrderAcceptedEmail($email, $data)
    {
        $mailer = new OrderAccepted($data);
        Mail::to($email)->send($mailer);
    }

    /**
     * Create 3 installment payments for the accepted order.
     * @param Order $order
     * @return void
     */
    private function createSchedulePayments(Order $order): void
    {
        try {
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

            $schedulePayments = [];
            // Create 3 installments due at 30, 60, and 90 days
            for ($i = 1; $i <= 3; $i++) {
                $dueDate = now()->addDays($i * 30)->toDateString();

                $schedulePayment = SchedulePayment::create([
                    'user_id' => $userId,
                    'seller_id' => $sellerId,
                    'order_id' => $order->id,
                    'instalment_number' => $i,
                    'due_date' => $dueDate,
                    'instalment_amount' => $installmentAmount,
                    'principle_amount' => $installmentAmount, // Use installment amount as principle for simplicity
                    'payment_method' => 'Card Payment',
                    'payment_status' => 'pending',
                    'assigned_to' => null, // Explicitly set to null
                ]);

                $schedulePayments[] = $schedulePayment;
            }

            // Log schedule payments creation
            $justificationData = $this->auditTrailService->withJustification(
                'Payment schedule creation required for installment-based orders as per agreement',
                'contractual_necessity',
                ['payment_schedule', 'installment_amounts', 'due_dates']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'payment_operations',
                'event_type' => 'schedule_payments_created',
                'entity_type' => 'SchedulePayment',
                'action_summary' => 'Created 3 installment payments for order #' . $order->id,
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $userId,
                    'seller_id' => $sellerId,
                    'total_order_amount' => $order->grand_total,
                    'installment_amount' => $installmentAmount,
                    'installment_count' => 3,
                    'first_due_date' => now()->addDays(30)->format('Y-m-d'),
                    'second_due_date' => now()->addDays(60)->format('Y-m-d'),
                    'third_due_date' => now()->addDays(90)->format('Y-m-d'),
                    'created_by' => Auth::id(),
                    'payment_ids' => array_map(fn($sp) => $sp->id, $schedulePayments)
                ]
            ], $justificationData));

            $this->logOrderAction(
                $order,
                'schedule_payments_created',
                "3 installment payments created, each for {$installmentAmount} SAR. Due dates at 30, 60, 90 days.",
                ['instalment_count' => 3, 'first_due_date' => now()->addDays(30)->format(dateFormat())]
            );
        } catch (\Exception $e) {
            Log::error('Failed to create schedule payments', [
                'error' => $e->getMessage(),
                'order_id' => $order->id
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'schedule_payments_failed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Failed to create schedule payments',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'grand_total' => $order->grand_total
                ]
            ]);
        }
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
            $this->auditTrailService->log([
                'event_category' => 'financial_operations',
                'event_type' => 'nafith_sanad_skipped_amount',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Nafith SANAD skipped - order amount below threshold',
                'properties' => [
                    'order_id' => $order->id,
                    'order_amount' => $order->grand_total,
                    'nafith_threshold' => $nafithMaxAmount,
                    'customer_id' => $user->id
                ]
            ]);
            return null;
        }

        if (Sanad::where('order_id', $order->id)->exists()) {
            $this->auditTrailService->log([
                'event_category' => 'financial_operations',
                'event_type' => 'nafith_sanad_exists',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Nafith SANAD creation skipped - SANAD already exists',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $user->id
                ]
            ]);
            return null;
        }

        // Check for missing required customer data
        if (empty($user->iqama) || empty($user->phone_number)) {
            $this->auditTrailService->log([
                'event_category' => 'validation_errors',
                'event_type' => 'nafith_sanad_missing_data',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Nafith SANAD creation failed - missing customer data',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $user->id,
                    'has_iqama' => !empty($user->iqama),
                    'has_phone' => !empty($user->phone_number),
                    'missing_fields' => empty($user->iqama) ? 'iqama' : (empty($user->phone_number) ? 'phone' : 'none')
                ]
            ]);
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
                'due_date'     => now()->addDays(90)->format(dateFormat()),
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
            $sanad = Sanad::create([
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

            // Log successful SANAD creation
            $justificationData = $this->auditTrailService->withJustification(
                'Nafith SANAD creation required for regulatory compliance and credit documentation',
                'legal_obligation',
                ['national_id', 'financial_amount', 'credit_agreement']
            );

            $this->auditTrailService->logCreated(
                $sanad,
                'Created Nafith SANAD for order #' . $order->id,
                array_merge([
                    'event_category' => 'financial_operations',
                    'event_type' => 'nafith_sanad_created',
                    'entity_type' => 'Sanad',
                ], $justificationData)
            );

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

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'nafith_sanad_creation_failed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Nafith SANAD creation failed with API error',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'customer_id' => $user->id,
                    'order_amount' => $order->grand_total,
                    'reference_id' => $referenceId
                ]
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
        try {
            $otp = rand(100000, 999999);
            $order->delivery_otp = $otp;

            // Log OTP generation
            $this->auditTrailService->log([
                'event_category' => 'delivery_operations',
                'event_type' => 'delivery_otp_generated',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Generated delivery OTP for order',
                'properties' => [
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id,
                    'otp' => '******', // Masked for security
                    'otp_last_4' => substr($otp, -4), // Last 4 digits for reference
                    'generated_by' => Auth::id()
                ]
            ]);

            $message = "Please submit this OTP on the ArabianPay mobile app to confirm your order delivery: {$otp}";
            $description = "Delivery OTP ({$otp}) generated and attempt to send to customer.";

            $sentSms = false;
            $sentEmail = false;

            if (!empty($order->user->phone_number)) {
                $this->sendSmsOtp($order->user->phone_number, $otp, $message);
                $sentSms = true;

                // Log SMS sent
                $this->auditTrailService->log([
                    'event_category' => 'notification_events',
                    'event_type' => 'delivery_otp_sms_sent',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Sent delivery OTP via SMS',
                    'properties' => [
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id,
                        'phone_number' => substr($order->user->phone_number, -4), // Last 4 digits only
                        'sms_provider' => 'default',
                        'timestamp' => now()->toISOString()
                    ]
                ]);
            }

            if (!empty($order->user->email)) {
                $this->sendEmailOtp($order->user->email, $otp, 'Order Delivery OTP', $message);
                $sentEmail = true;

                // Log email sent
                $this->auditTrailService->log([
                    'event_category' => 'notification_events',
                    'event_type' => 'delivery_otp_email_sent',
                    'entity_type' => 'Order',
                    'entity_id' => $order->id,
                    'action_summary' => 'Sent delivery OTP via email',
                    'properties' => [
                        'order_id' => $order->id,
                        'customer_id' => $order->user_id,
                        'email_domain' => substr(strrchr($order->user->email, "@"), 1), // Domain only
                        'timestamp' => now()->toISOString()
                    ]
                ]);
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

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'delivery_otp_failed',
                'entity_type' => 'Order',
                'entity_id' => $order->id,
                'action_summary' => 'Failed to send delivery OTP',
                'properties' => [
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                    'customer_id' => $order->user_id
                ]
            ]);

            $this->logOrderAction(
                $order,
                'delivery_otp_send_failed',
                $description,
                [
                    'otp' => $otp ?? 'not_generated',
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
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'access_control',
                'event_type' => 'unauthorized_order_action',
                'entity_type' => 'Order',
                'action_summary' => 'Unauthorized user attempted order action',
                'properties' => [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'is_manager' => $user->is_manager,
                    'ip_address' => request()->ip(),
                    'timestamp' => now()->toISOString()
                ]
            ]);

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

    /**
     * Helper method to identify changed fields in order updates
     *
     * @param array $beforeState
     * @param array $afterState
     * @return array
     */
    private function getOrderChangedFields(array $beforeState, array $afterState): array
    {
        $changed = [];
        $sensitiveFields = ['delivery_otp', 'user_id', 'seller_id', 'shipping_address', 'billing_address'];

        foreach ($beforeState as $key => $value) {
            if (isset($afterState[$key]) && $afterState[$key] != $value) {
                if (in_array($key, $sensitiveFields)) {
                    $changed[$key] = [
                        'old' => '***MASKED***',
                        'new' => '***MASKED***',
                        'changed' => true
                    ];
                } else {
                    $changed[$key] = [
                        'old' => $value,
                        'new' => $afterState[$key]
                    ];
                }
            }
        }

        // Check for new fields that weren't in before state
        foreach ($afterState as $key => $value) {
            if (!isset($beforeState[$key])) {
                if (in_array($key, $sensitiveFields)) {
                    $changed[$key] = [
                        'old' => null,
                        'new' => '***MASKED***'
                    ];
                } else {
                    $changed[$key] = [
                        'old' => null,
                        'new' => $value
                    ];
                }
            }
        }

        return $changed;
    }
}
