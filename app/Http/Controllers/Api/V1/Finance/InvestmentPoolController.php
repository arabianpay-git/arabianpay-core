<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\InvestmentPoolResource;
use App\Models\InvestmentPool;
use App\Services\PoolService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentPoolController extends Controller
{
    public function __construct(
        protected PoolService $poolService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = InvestmentPool::orderBy('start_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->get('per_page', 15), 100);
        $pools = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => InvestmentPoolResource::collection($pools),
            'meta' => [
                'current_page' => $pools->currentPage(),
                'last_page' => $pools->lastPage(),
                'per_page' => $pools->perPage(),
                'total' => $pools->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'expected_collections' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $pool = InvestmentPool::create($validated);

        return response()->json([
            'success' => true,
            'data' => new InvestmentPoolResource($pool),
            'message' => 'Investment pool created',
        ], 201);
    }

    public function show(InvestmentPool $pool): JsonResponse
    {
        $pool->load(['checkouts.schedulePayments.payments']);

        $checkouts = $pool->checkouts->map(fn ($c) => [
            'id' => $c->id,
            'total_amount' => (float) $c->total_amount,
            'status' => $c->status,
            'payment_rate' => $c->schedulePayments->where('payment_status', 'paid')->count() > 0
                ? round(($c->schedulePayments->where('payment_status', 'paid')->sum('instalment_amount') / max($c->schedulePayments->sum('instalment_amount'), 1)) * 100, 1)
                : 0,
        ]);

        $schedulePayments = $pool->schedulePayments()->with(['user', 'payment'])->paginate(20);

        $installmentStats = [
            'total' => $pool->schedulePayments->count(),
            'paid' => $pool->schedulePayments->where('payment_status', 'paid')->count(),
            'pending' => $pool->schedulePayments->where('payment_status', 'pending')->count(),
            'late' => $pool->schedulePayments->where('payment_status', 'late')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'pool' => new InvestmentPoolResource($pool),
                'checkouts' => $checkouts,
                'schedule_payments' => $schedulePayments,
                'stats' => $installmentStats,
            ],
        ]);
    }

    public function update(Request $request, InvestmentPool $pool): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'expected_collections' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,closed',
            'description' => 'nullable|string',
        ]);

        $pool->update($validated);

        return response()->json([
            'success' => true,
            'data' => new InvestmentPoolResource($pool->fresh()),
            'message' => 'Investment pool updated',
        ]);
    }

    public function destroy(InvestmentPool $pool): JsonResponse
    {
        if ($pool->checkouts()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete pool with associated checkouts',
            ], 409);
        }

        $pool->delete();

        return response()->json([
            'success' => true,
            'message' => 'Investment pool deleted',
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        $start = $request->filled('start') ? Carbon::parse($request->start) : Carbon::now()->startOfMonth();
        $end = $request->filled('end') ? Carbon::parse($request->end) : Carbon::now()->endOfMonth();

        $pools = InvestmentPool::whereBetween('start_date', [$start, $end])
            ->orWhereBetween('end_date', [$start, $end])
            ->get();

        $events = $pools->map(fn ($pool) => [
            'id' => $pool->id,
            'title' => $pool->name.' ('.($pool->collection_rate ?? 0).'%)',
            'start' => $pool->start_date->toDateString(),
            'end' => $pool->end_date->toDateString(),
            'color' => match (true) {
                ($pool->collection_rate ?? 0) >= 90 => '#28a745',
                ($pool->collection_rate ?? 0) >= 70 => '#ffc107',
                default => '#dc3545',
            },
            'extendedProps' => [
                'status' => $pool->status,
                'collection_rate' => $pool->collection_rate,
                'total_disbursed' => $pool->total_disbursed,
                'total_collected' => $pool->total_collected,
            ],
        ]);

        return response()->json($events);
    }
}
