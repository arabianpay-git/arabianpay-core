<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\SchedulePaymentResource;
use App\Models\SchedulePayment;
use App\Services\AuditTrailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SchedulePaymentController extends Controller
{
    protected const VALID_STATUSES = ['pending', 'paid', 'overdue', 'cancelled', 'failed'];

    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    public function index(): JsonResponse
    {
        $query = SchedulePayment::with(['user', 'assigned']);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('assigned_to', Auth::id());
        }

        $perPage = min((int) request('per_page', 15), 100);
        $payments = $query->orderBy('due_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SchedulePaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function byStatus($status): JsonResponse
    {
        if (! in_array($status, self::VALID_STATUSES, true)) {
            return response()->json(['success' => false, 'message' => 'Invalid status'], 422);
        }

        $query = SchedulePayment::with(['user', 'assigned'])
            ->where('payment_status', $status);

        if (! Auth::user()->hasRole('admin')) {
            $query->where('assigned_to', Auth::id());
        }

        $perPage = min((int) request('per_page', 15), 100);
        $payments = $query->orderBy('due_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SchedulePaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function show(SchedulePayment $schedulePayment): JsonResponse
    {
        $schedulePayment->load(['assigned', 'user', 'checkout.orders', 'payment', 'claims']);

        return response()->json([
            'success' => true,
            'data' => new SchedulePaymentResource($schedulePayment),
        ]);
    }

    public function update(Request $request, SchedulePayment $schedulePayment): JsonResponse
    {
        $validated = $request->validate([
            'due_date' => 'required|date|after_or_equal:today',
        ]);

        $schedulePayment->update([
            'due_date' => $validated['due_date'],
            'payment_status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => new SchedulePaymentResource($schedulePayment->fresh()->load(['user', 'assigned'])),
            'message' => 'Schedule payment updated',
        ]);
    }

    public function payNow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedule_payments,id',
            'payment_method' => 'required|string|max:50',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $schedulePayment = SchedulePayment::findOrFail($validated['schedule_id']);

        $data = [
            'payment_method' => $validated['payment_method'],
            'paid_at' => now(),
            'payment_status' => 'paid',
        ];

        if ($request->hasFile('receipt')) {
            $path = $request->file('receipt')->store('receipts', 'public');
            $data['receipt'] = $path;
        }

        $schedulePayment->update($data);

        return response()->json([
            'success' => true,
            'data' => new SchedulePaymentResource($schedulePayment->fresh()->load(['user', 'assigned'])),
            'message' => 'Payment recorded successfully',
        ]);
    }
}
