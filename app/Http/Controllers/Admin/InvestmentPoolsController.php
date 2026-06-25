<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestmentPoolRequest;
use App\Http\Requests\UpdateInvestmentPoolRequest;
use App\Models\InvestmentPool;
use App\Services\PoolService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvestmentPoolsController extends Controller
{
    protected $poolService;

    public function __construct(PoolService $poolService)
    {
        $this->poolService = $poolService;
    }

    /**
     * Display the investment pools calendar
     */
    public function calendar()
    {
        return view('admin.investment-pools.calendar');
    }

    /**
     * Get calendar events (FullCalendar format)
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $start = $request->get('start');
        $end = $request->get('end');

        $query = InvestmentPool::query();

        // Filter by date range if provided
        if ($start && $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                    });
            });
        }

        $pools = $query->orderBy('start_date')->get();

        $events = $pools->map(function ($pool) {
            $pool->updateMetrics();
            // Determine color based on performance
            $backgroundColor = $this->getPoolColor($pool);

            return [
                'id' => $pool->id,
                'title' => $pool->name,
                'start' => Carbon::parse($pool->start_date)->format(dateFormat()),
                'end' => Carbon::parse($pool->end_date)->addDay()->format(dateFormat()), // FullCalendar end date is exclusive
                'backgroundColor' => $backgroundColor,
                'borderColor' => $backgroundColor,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'poolData' => [
                        'id' => $pool->id,
                        'uuid' => $pool->uuid,
                        'name' => $pool->name,
                        'start_date' => Carbon::parse($pool->start_date)->format(dateFormat()),
                        'end_date' => Carbon::parse($pool->end_date)->format(dateFormat()),
                        'status' => $pool->status,
                        'total_disbursed' => $pool->total_disbursed,
                        'total_collected' => $pool->total_collected,
                        'expected_collections' => $pool->expected_collections,
                        'collection_rate' => $pool->collection_rate,
                        'total_checkouts' => $pool->total_checkouts,
                    ],
                ],
            ];
        });

        return response()->json($events);
    }

    /**
     * Store a new investment pool
     */
    public function store(StoreInvestmentPoolRequest $request): JsonResponse
    {

        try {
            $pool = InvestmentPool::create([
                'uuid' => Str::uuid(),
                'name' => $request->name,
                'start_date' => Carbon::parse($request->start_date),
                'end_date' => Carbon::parse($request->end_date),
                'status' => 'active',
            ]);

            // Compute metrics via the service/model method if available
            $pool->updateMetrics();
            $pool->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Investment pool created successfully',
                'pool' => $pool,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating investment pool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified investment pool
     */
    public function show(InvestmentPool $pool)
    {
        // Update pool metrics
        $pool->updateMetrics();

        // Get checkouts with pagination
        $checkouts = $pool->checkouts()
            ->with(['user', 'schedulePayments'])
            ->latest()
            ->paginate(20);

        // Calculate payment rate for each checkout
        $checkouts->getCollection()->transform(function ($checkout) {
            $totalInstallments = $checkout->schedulePayments->count();
            $paidInstallments = $checkout->schedulePayments->where('payment_status', 'paid')->count();
            $checkout->payment_rate = $totalInstallments > 0 ? ($paidInstallments / $totalInstallments) * 100 : 0;

            // Get next payment
            $nextPayment = $checkout->schedulePayments
                ->where('payment_status', '!=', 'paid')
                ->where('due_date', '>=', now())
                ->sortBy('due_date')
                ->first();

            if ($nextPayment) {
                $checkout->next_payment_date = $nextPayment->due_date;
                $checkout->next_payment_amount = $nextPayment->instalment_amount;
            }

            return $checkout;
        });

        // Get all schedule payments for this pool
        // Direct relationship: investment_pool -> checkouts -> schedule_payments
        $schedulePayments = \App\Models\SchedulePayment::whereIn('checkout_id', $pool->checkouts->pluck('id'))
            ->orderBy('due_date')
            ->get();

        // Calculate installment statistics
        $installmentStats = [
            'total' => $schedulePayments->count(),
            'paid' => $schedulePayments->where('payment_status', 'paid')->count(),
            'pending' => $schedulePayments->where('payment_status', 'pending')->count(),
            'late' => $schedulePayments->where('payment_status', 'late')->count(),
            'due' => $schedulePayments->where('payment_status', 'due')->count(),
            'overdue' => $schedulePayments->where('due_date', '<', now())->where('payment_status', '!=', 'paid')->count(),
        ];

        // Calculate payment status statistics
        $paymentStats = [
            'paid' => $installmentStats['paid'],
            'pending' => $installmentStats['pending'],
            'due' => $installmentStats['due'],
            'late' => $installmentStats['late'],
        ];

        // Group payments by month for timeline
        $monthlyPayments = $schedulePayments->groupBy(function ($payment) {
            return Carbon::parse($payment->due_date)->format('Y-m');
        })->map(function ($monthPayments, $month) {
            $total = $monthPayments->count();
            $paid = $monthPayments->where('payment_status', 'paid')->count();
            $pending = $monthPayments->where('payment_status', 'pending')->count();
            $due = $monthPayments->where('payment_status', 'due')->count();
            $late = $monthPayments->where('payment_status', 'late')->count();

            $totalAmount = $monthPayments->sum('instalment_amount');
            $collectedAmount = $monthPayments->where('payment_status', 'paid')->sum('instalment_amount');

            return [
                'month' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
                'total_count' => $total,
                'paid_count' => $paid,
                'pending_count' => $pending,
                'due_count' => $due,
                'late_count' => $late,
                'paid_percentage' => $total > 0 ? round(($paid / $total) * 100, 2) : 0,
                'pending_percentage' => $total > 0 ? round(($pending / $total) * 100, 2) : 0,
                'due_percentage' => $total > 0 ? round(($due / $total) * 100, 2) : 0,
                'late_percentage' => $total > 0 ? round(($late / $total) * 100, 2) : 0,
                'total_amount' => $totalAmount,
                'collected_amount' => $collectedAmount,
                'collection_rate' => $totalAmount > 0 ? round(($collectedAmount / $totalAmount) * 100, 2) : 0,
            ];
        })->sortBy('month');

        // Generate collection trend data (last 30 days)
        $collectionTrend = [];
        $collectionDates = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->format(dateFormat());
            $collectionDates[] = $date->format('M d');

            // Get payments collected on this date
            $dailyCollection = $schedulePayments
                ->where('payment_status', 'paid')
                ->filter(function ($payment) use ($date) {
                    // Assuming payments have updated_at when status changes to paid
                    return Carbon::parse($payment->updated_at)->isSameDay($date);
                })
                ->sum('instalment_amount');

            $collectionTrend[] = $dailyCollection;
        }

        // Get customers and suppliers participating in this pool
        $checkoutIds = $pool->checkouts->pluck('id');

        // Get unique customers from checkouts
        $customers = \App\Models\Customer::whereHas('checkouts', function ($query) use ($checkoutIds) {
            $query->whereIn('id', $checkoutIds)
                ->with(['payments' => function ($query) use ($checkoutIds) {
                    $query->whereIn('checkout_id', $checkoutIds);
                }]);
        })
            ->with(['user', 'checkouts' => function ($query) use ($checkoutIds) {
                $query->whereIn('id', $checkoutIds);
            }])

            ->get()
            ->map(function ($customer) {
                $checkouts = $customer->checkouts;

                // Get all payments for these checkouts
                $checkoutIds = $checkouts->pluck('id');
                $paymentsSum = \App\Models\Payment::whereHas('schedulePayment', function ($q) use ($checkoutIds) {
                    $q->whereIn('checkout_id', $checkoutIds);
                })->sum('amount');

                $paymentsCount = \App\Models\Payment::whereHas('schedulePayment', function ($q) use ($checkoutIds) {
                    $q->whereIn('checkout_id', $checkoutIds);
                })->count();

                $totalAmount = $checkouts->sum('total_amount');
                $collectionsPercent = $totalAmount > 0 ? round($paymentsSum / $totalAmount * 100, 2) : 0;

                return (object) [
                    'id' => $customer->id,
                    'user_id' => $customer->user_id,
                    'user' => $customer->user,
                    'name' => $customer->user->business_name ?? $customer->user->name ?? 'N/A',
                    'orders_count' => $checkouts->count(),
                    'orders_amount' => number_format($totalAmount, 2),
                    'payments_count' => $paymentsCount,
                    'payments_amount' => number_format($paymentsSum, 2),
                    'collection_percent' => number_format($collectionsPercent, 2),
                ];
            });

        // Get unique suppliers (merchants) from checkouts
        // First, get all unique seller_ids from orders in these checkouts
        $sellerIds = \App\Models\Order::whereIn('checkout_id', $checkoutIds)
            ->distinct()
            ->pluck('seller_id')
            ->filter();

        $suppliers = \App\Models\Merchant::whereIn('id', $sellerIds)
            ->with('user')
            ->get()
            ->map(function ($merchant) use ($checkoutIds) {
                // Get orders for this merchant in these checkouts
                $orders = \App\Models\Order::where('seller_id', $merchant->id)
                    ->whereIn('checkout_id', $checkoutIds)
                    ->get();

                return (object) [
                    'id' => $merchant->id,
                    'user_id' => $merchant->user_id,
                    'name' => $merchant->user->business_name ?? $merchant->user->name ?? 'N/A',
                    'orders_count' => $orders->count(),
                    'orders_amount' => number_format($orders->sum('grand_total'), 2).' SR',
                ];
            });

        return view('admin.investment-pools.show', compact(
            'pool',
            'checkouts',
            'schedulePayments',
            'installmentStats',
            'paymentStats',
            'monthlyPayments',
            'collectionTrend',
            'collectionDates',
            'customers',
            'suppliers'
        ));
    }

    /**
     * Update the specified investment pool
     */
    public function update(UpdateInvestmentPoolRequest $request, InvestmentPool $pool): JsonResponse
    {

        try {
            $pool->update($request->only([
                'name',
                'start_date',
                'end_date',
                'status',
            ]));

            // Recalculate metrics if needed
            // $this->poolService->updatePoolMetrics($pool);

            return response()->json([
                'success' => true,
                'message' => 'Investment pool updated successfully',
                'pool' => $pool->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating investment pool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified investment pool
     */
    public function destroy(InvestmentPool $pool): JsonResponse
    {
        try {
            // Check if pool has associated checkouts
            if ($pool->checkouts()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete pool with associated checkouts',
                ], 400);
            }

            $pool->delete();

            return response()->json([
                'success' => true,
                'message' => 'Investment pool deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting investment pool: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get color for pool based on performance
     */
    private function getPoolColor(InvestmentPool $pool): string
    {
        if ($pool->status !== 'active') {
            return '#7e8299'; // Gray for closed/inactive
        }

        $rate = floatval($pool->collection_rate ?? 0);

        if ($rate >= 90) {
            return '#50cd89'; // Green for high performance
        } elseif ($rate >= 70) {
            return '#ffc700'; // Yellow for medium performance
        } else {
            return '#f1416c'; // Red for low performance
        }
    }
}
