<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\PartialPaymentResource;
use App\Http\Resources\Finance\PromiseResource;
use App\Http\Resources\Finance\SchedulePaymentResource;
use App\Models\Order;
use App\Models\PartialPayment;
use App\Models\Promise;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(
        protected AuditTrailService $auditTrail
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from) : Carbon::now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to) : Carbon::now()->endOfMonth();

        $outstandingQuery = SchedulePayment::whereIn('payment_status', ['pending', 'due', 'late']);
        $overdueQuery = SchedulePayment::where('payment_status', 'late');

        $totalOutstanding = (float) $outstandingQuery->sum('instalment_amount');
        $overdueAmount = (float) $overdueQuery->sum('instalment_amount');

        $totalPaid = (float) SchedulePayment::where('payment_status', 'paid')
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->sum('instalment_amount');

        $totalDue = (float) SchedulePayment::whereIn('payment_status', ['pending', 'due', 'late'])
            ->whereBetween('due_date', [$dateFrom, $dateTo])
            ->sum('instalment_amount');

        $collectionRate = $totalDue > 0 ? round(($totalPaid / $totalDue) * 100, 1) : 0;
        $activePromises = Promise::whereNull('status')->orWhere('status', 'pending')->count();

        $dpdBuckets = [
            ['bucket' => '0-30', 'count' => SchedulePayment::where('payment_status', 'late')->where('late_days', '<=', 30)->count()],
            ['bucket' => '31-60', 'count' => SchedulePayment::where('payment_status', 'late')->whereBetween('late_days', [31, 60])->count()],
            ['bucket' => '61-90', 'count' => SchedulePayment::where('payment_status', 'late')->whereBetween('late_days', [61, 90])->count()],
            ['bucket' => '90+', 'count' => SchedulePayment::where('payment_status', 'late')->where('late_days', '>', 90)->count()],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => [
                    'total_outstanding' => $totalOutstanding,
                    'overdue_amount' => $overdueAmount,
                    'collection_rate' => $collectionRate,
                    'active_promises' => $activePromises,
                ],
                'dpd_distribution' => $dpdBuckets,
                'date_from' => $dateFrom->toDateString(),
                'date_to' => $dateTo->toDateString(),
            ],
        ]);
    }

    public function installments(Request $request): JsonResponse
    {
        $query = SchedulePayment::with(['user', 'assigned', 'checkout']);

        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', Carbon::parse($request->from));
        }
        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', Carbon::parse($request->to));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"))
                ->orWhere('order_id', 'like', "%{$search}%");
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $installments = $query->orderBy('due_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SchedulePaymentResource::collection($installments),
            'meta' => [
                'current_page' => $installments->currentPage(),
                'last_page' => $installments->lastPage(),
                'per_page' => $installments->perPage(),
                'total' => $installments->total(),
            ],
        ]);
    }

    public function installmentsCalendar(Request $request): JsonResponse
    {
        $start = $request->filled('start') ? Carbon::parse($request->start) : Carbon::now()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->end) : Carbon::now()->endOfMonth();

        $payments = SchedulePayment::with(['user'])
            ->whereBetween('due_date', [$start, $end])
            ->get();

        $events = $payments->map(fn ($p) => [
            'title' => "{$p->instalment_amount} SAR - #{$p->order_id}",
            'start' => $p->due_date->toDateString(),
            'className' => match ($p->payment_status) {
                'paid' => 'bg-success',
                'late' => 'bg-danger',
                'pending' => 'bg-warning',
                default => 'bg-info',
            },
            'extendedProps' => [
                'id' => $p->id,
                'status' => $p->payment_status,
                'amount' => $p->instalment_amount,
                'order_id' => $p->order_id,
                'customer' => $p->user?->name,
            ],
        ]);

        return response()->json($events);
    }

    public function installmentDetail(Order $order): JsonResponse
    {
        $schedulePayments = SchedulePayment::where('order_id', $order->id)
            ->with(['user', 'payment'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'grand_total' => (float) $order->grand_total,
                    'delivery_status' => $order->delivery_status,
                ],
                'schedule_payments' => SchedulePaymentResource::collection($schedulePayments),
            ],
        ]);
    }

    public function alerts(): JsonResponse
    {
        $criticalDpd = SchedulePayment::where('payment_status', 'late')
            ->where('late_days', '>', 90)
            ->count();

        $brokenPromises = Promise::where('promise_date', '<', now())
            ->whereNull('status')
            ->count();

        $failedPayments = SchedulePayment::where('payment_status', 'failed')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                ['type' => 'critical_dpd', 'label' => 'Critical DPD > 90 days', 'count' => $criticalDpd],
                ['type' => 'broken_promises', 'label' => 'Broken Promises', 'count' => $brokenPromises],
                ['type' => 'failed_payments_7d', 'label' => 'Failed Payments (7 days)', 'count' => $failedPayments],
            ],
        ]);
    }

    public function flags(): JsonResponse
    {
        $highValueAtRisk = SchedulePayment::where('payment_status', 'late')
            ->where('instalment_amount', '>', 10000)
            ->count();

        $promisesDueToday = Promise::whereDate('promise_date', today())
            ->whereNull('status')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                ['type' => 'high_value_at_risk', 'label' => 'High-Value At Risk (>10K)', 'count' => $highValueAtRisk],
                ['type' => 'promises_due_today', 'label' => 'Promises Due Today', 'count' => $promisesDueToday],
            ],
        ]);
    }

    public function allocations(): JsonResponse
    {
        $grouped = SchedulePayment::with(['user'])
            ->whereIn('payment_status', ['pending', 'due'])
            ->get()
            ->groupBy('order_id')
            ->map(fn ($items, $orderId) => [
                'order_id' => $orderId,
                'total_due' => (float) $items->sum('instalment_amount'),
                'installments' => $items->map(fn ($i) => [
                    'id' => $i->id,
                    'instalment_number' => $i->instalment_number,
                    'amount' => (float) $i->instalment_amount,
                    'due_date' => $i->due_date,
                    'status' => $i->payment_status,
                ])->values(),
            ])->values();

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    public function promises(Request $request): JsonResponse
    {
        $query = Promise::with(['user', 'schedulePayment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $items = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PromiseResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function partialPayments(Request $request): JsonResponse
    {
        $query = PartialPayment::with(['user', 'schedulePayment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $payments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => PartialPaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function updatePartialPaymentStatus(Request $request, PartialPayment $partialPayment): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
        ]);

        $partialPayment->update([
            'approval_status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
            'status' => $validated['action'] === 'approve' ? 'paid' : 'cancelled',
        ]);

        return response()->json([
            'success' => true,
            'data' => new PartialPaymentResource($partialPayment->fresh()),
            'message' => "Partial payment {$validated['action']}d",
        ]);
    }

    public function pendingInstallments(User $user): JsonResponse
    {
        $installments = SchedulePayment::where('user_id', $user->id)
            ->whereIn('payment_status', ['pending', 'due', 'late'])
            ->get(['id', 'instalment_amount', 'due_date']);

        return response()->json([
            'success' => true,
            'data' => $installments,
        ]);
    }
}
