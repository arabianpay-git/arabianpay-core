<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\RefundRequestResource;
use App\Models\RefundRequest;
use App\Services\Finance\RefundApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RefundRequestController extends Controller
{
    protected const VALID_STATUSES = ['pending', 'approved', 'rejected'];

    public function __construct(
        protected RefundApprovalService $refundApprovalService
    ) {}

    public function index(): JsonResponse
    {
        $query = RefundRequest::with(['user', 'seller', 'assigned']);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('assigned_to', Auth::id());
        }

        $perPage = min((int) request('per_page', 15), 100);
        $refunds = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => RefundRequestResource::collection($refunds),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function byStatus($status): JsonResponse
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 422);
        }

        $query = RefundRequest::with(['user', 'seller', 'assigned'])
            ->where('refund_status', $status);

        $perPage = min((int) request('per_page', 15), 100);
        $refunds = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => RefundRequestResource::collection($refunds),
            'meta' => [
                'current_page' => $refunds->currentPage(),
                'last_page' => $refunds->lastPage(),
                'per_page' => $refunds->perPage(),
                'total' => $refunds->total(),
            ],
        ]);
    }

    public function updateStatus(Request $request, RefundRequest $refundRequest): JsonResponse
    {
        $validated = $request->validate([
            'refund_status' => 'required|in:pending,approved,rejected',
        ]);

        try {
            $this->refundApprovalService->approve($refundRequest, Auth::user(), $validated['refund_status']);

            return response()->json([
                'success' => true,
                'data' => new RefundRequestResource($refundRequest->fresh()->load(['user', 'seller', 'assigned'])),
                'message' => 'Refund status updated',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
