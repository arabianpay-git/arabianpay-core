<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefundRequestController extends Controller
{
    public function updateRefundStatus(Request $request, $id)
    {
        $request->validate([
            'refund_status' => 'required|in:pending,approved,rejected',
        ]);

        $refundRequest = RefundRequest::findOrFail($id);
        $oldStatus = $refundRequest->refund_status;
        $newStatus = $request->input('refund_status');

        $refundRequest->update([
            'refund_status' => $newStatus,
        ]);

        // ===== Log the refund status update =====
        $refundRequest->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated refund request status to {$refundRequest->refund_status} for order ID {$refundRequest->order_id}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

        // ===== Send Firebase Notification ======
        try {
            $firebaseService = app(FirebaseService::class);

            // Professional notification title
            $notificationTitle = "Refund Request #{$refundRequest->id} Status Updated";

            // Body text based on new status
            $statusMessages = [
                'pending' => "Your refund request for Order #{$refundRequest->order_id} is now pending review.",
                'approved' => "Good news! Your refund request for Order #{$refundRequest->order_id} has been approved.",
                'rejected' => "Your refund request for Order #{$refundRequest->order_id} has been rejected. Please contact support for details.",
            ];

            $notificationBody = $statusMessages[$newStatus] ?? "Your refund request status has changed.";

            $firebaseService->sendCustomNotification(
                $refundRequest->user_id, // Assuming refundRequest has user_id
                $notificationTitle,
                $notificationBody,
                [
                    'refund_request_id' => $refundRequest->id,
                    'order_id' => $refundRequest->order_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]
            );
        } catch (\Throwable $e) {
            Log::error("Failed to send refund notification: " . $e->getMessage(), ['refund_request_id' => $refundRequest->id]);
        }
        // ========================================

        return redirect()->back()->with('success', 'Refund status updated successfully!');
    }

    public function refundRequests()
    {
        $user = currentUser();
        $refundRequests = RefundRequest::with('user', 'order')
            ->select('id', 'assigned_to', 'user_id', 'seller_id', 'order_id', 'refund_amount', 'refund_status', 'created_at')
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })->orderByRaw('assigned_to IS NULL DESC')
            ->paginate(10);
        $status = 'Refund';
        return view('admin.refund-requests.index', compact('refundRequests', 'status'));
    }

    public function showRefundRequests($status)
    {
        $statuses = ['pending', 'approved', 'rejected'];

        if (!in_array($status, $statuses)) {
            abort(404);
        }
        $user = currentUser();
        $refundRequests = RefundRequest::with('user', 'order')
            ->where('refund_status', $status)
            ->select('id', 'assigned_to', 'user_id', 'seller_id', 'order_id', 'refund_amount', 'refund_status', 'created_at')
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })->orderByRaw('assigned_to IS NULL DESC')
            ->paginate(10);

        return view('admin.refund-requests.index', compact('refundRequests', 'status'));
    }
}
