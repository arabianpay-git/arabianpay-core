<?php

namespace App\Http\Controllers;

use App\Models\RefundRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RefundRequestController extends Controller
{
    public function updateRefundStatus(Request $request, $id)
    {
        $request->validate([
            'refund_status' => 'required|in:pending,approved,rejected',
        ]);

        $refundRequest = RefundRequest::findOrFail($id);

        $refundRequest->update([
            'refund_status' => $request->input('refund_status'),
        ]);

        // Log the refund status update
        $refundRequest->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated refund request status to {$refundRequest->refund_status} for order ID {$refundRequest->order_id}",
            properties: [
                'ip' => request()->ip(),
                'batch_uuid' => (string) Str::uuid(),
            ],
        );

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
