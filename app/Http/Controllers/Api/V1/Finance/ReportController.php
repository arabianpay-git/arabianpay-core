<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\CreditAssessmentService;
use App\Services\PortfolioPerformanceService;
use App\Services\RiskAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function portfolioPerformance(Request $request, PortfolioPerformanceService $service): JsonResponse
    {
        $filters = $request->only(['from', 'to', 'merchant_id']);
        $reports = $service->getReport($filters);

        return response()->json([
            'success' => true,
            'data' => $reports,
        ]);
    }

    public function financialSummary(Request $request): JsonResponse
    {
        $fromDate = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $toDate = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;
        $merchantId = $request->input('merchant_id');

        $query = Transaction::query();
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }
        if ($merchantId) {
            $query->where('seller_id', $merchantId);
        }

        $totalRevenue = (float) (clone $query)->sum('collected');
        $supplierPayouts = (float) (clone $query)->sum('retrieved');
        $operationalCosts = $totalRevenue * 0.015;
        $netProfit = $totalRevenue - $supplierPayouts - $operationalCosts;

        $monthsCount = 6;
        $endDate = $toDate ?? now();
        $startDate = $fromDate ?? now()->subMonths($monthsCount - 1)->startOfMonth();
        $monthlyLabels = [];
        $monthlyRevenue = [];

        for ($i = 0; $i < $monthsCount; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthQuery = Transaction::query();
            if ($fromDate) {
                $monthQuery->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $monthQuery->where('created_at', '<=', $toDate);
            }
            if ($merchantId) {
                $monthQuery->where('seller_id', $merchantId);
            }
            $monthQuery->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()]);
            $monthlyRevenue[] = round((float) $monthQuery->sum('collected'), 2);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_revenue' => round($totalRevenue, 2),
                    'supplier_payouts' => round($supplierPayouts, 2),
                    'operational_costs' => round($operationalCosts, 2),
                    'net_profit' => round($netProfit, 2),
                ],
                'charts' => [
                    'months' => $monthlyLabels,
                    'monthly_revenue' => $monthlyRevenue,
                ],
            ],
        ]);
    }

    public function delinquencyAging(Request $request): JsonResponse
    {
        $fromDate = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : null;
        $toDate = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : null;
        $merchantId = $request->input('merchant_id');
        $today = now()->startOfDay();

        $query = SchedulePayment::where('payment_status', '!=', 'paid');
        if ($fromDate) {
            $query->where('due_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('due_date', '<=', $toDate);
        }
        if ($merchantId) {
            $query->where('seller_id', $merchantId);
        }

        $instalments = $query->select('seller_id', 'due_date', 'instalment_amount')->get();
        $delinquencyData = [];

        foreach ($instalments as $instalment) {
            $mId = $instalment->seller_id;
            $dpd = $today->diffInDays($instalment->due_date, false);
            if ($dpd <= 0) {
                continue;
            }

            if (! isset($delinquencyData[$mId])) {
                $delinquencyData[$mId] = ['dpd_1_15' => 0, 'dpd_16_30' => 0, 'dpd_31_60' => 0, 'dpd_over_60' => 0, 'total_outstanding' => 0];
            }

            $amount = (float) $instalment->instalment_amount;
            match (true) {
                $dpd <= 15 => $delinquencyData[$mId]['dpd_1_15'] += $amount,
                $dpd <= 30 => $delinquencyData[$mId]['dpd_16_30'] += $amount,
                $dpd <= 60 => $delinquencyData[$mId]['dpd_31_60'] += $amount,
                default => $delinquencyData[$mId]['dpd_over_60'] += $amount,
            };
            $delinquencyData[$mId]['total_outstanding'] += $amount;
        }

        $merchantNames = User::whereIn('id', array_keys($delinquencyData))
            ->select('id', 'first_name', 'last_name', 'business_name')
            ->get()
            ->keyBy('id');

        return response()->json([
            'success' => true,
            'data' => [
                'delinquency' => collect($delinquencyData)->map(fn ($d, $id) => [
                    'merchant_id' => $id,
                    'merchant_name' => $merchantNames[$id]->name ?? 'Unknown',
                    ...$d,
                ])->values(),
            ],
        ]);
    }

    public function supplierTransaction(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Merchant::with('user');
        if ($request->filled('from')) {
            $query->whereHas('user', fn ($q) => $q->whereDate('created_at', '>=', $request->from));
        }
        if ($request->filled('to')) {
            $query->whereHas('user', fn ($q) => $q->whereDate('created_at', '<=', $request->to));
        }
        if ($request->filled('merchant_id')) {
            $query->where('user_id', $request->merchant_id);
        }

        $merchants = $query->get();
        $orders = Order::whereNotNull('seller_id')->get()->groupBy('seller_id');

        $supplierData = $merchants->map(function ($merchant) use ($orders) {
            $merchantOrders = $orders->get($merchant->user_id, collect());
            $totalOrderAmount = $merchantOrders->sum(fn ($o) => $o->product_details ? map_product_details($o->product_details)->sum('total') : 0);
            $walletTransactions = Wallet::where('seller_id', $merchant->user_id)->where('transaction_type', 'seller_payment')->get();
            $fulfilledCount = $merchantOrders->where('delivery_status', 'delivered')->count();
            $totalCount = $merchantOrders->count();

            return [
                'merchant_id' => $merchant->id,
                'merchant_name' => $merchant->user?->name ?? 'Unknown',
                'order_volume' => round($totalOrderAmount, 2),
                'fulfillment_count' => $fulfilledCount,
                'fulfillment_percentage' => $totalCount > 0 ? round(($fulfilledCount / $totalCount) * 100, 2) : 0,
                'total_payouts' => round($walletTransactions->sum('amount'), 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $supplierData,
        ]);
    }

    public function collectionEfficiency(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'collection_rate' => 0,
                'total_recovered' => 0,
                'promises_kept' => 0,
                'note' => 'Collection efficiency data requires configuration.',
            ],
        ]);
    }

    public function riskExposure(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'total_exposure_percentage' => 75.5,
                'average_score' => 82.3,
                'delinquency_percentage' => 0,
                'high_risk_count' => 15,
                'segments' => ['Segment A', 'Segment B', 'Segment C', 'Segment D', 'Segment E'],
                'exposure_percentages' => [40, 70, 85, 90, 75],
            ],
        ]);
    }

    public function instalmentRepayment(Request $request): JsonResponse
    {
        $query = SchedulePayment::with('user')->orderBy('due_date', 'asc');
        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', $request->to);
        }
        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        }

        $grouped = $query->get()->groupBy('order_id');
        $page = (int) $request->get('page', 1);
        $perPage = 10;
        $paginated = $grouped->forPage($page, $perPage)->map(fn ($items, $orderId) => [
            'order_id' => $orderId,
            'total_amount' => (float) $items->sum('instalment_amount'),
            'paid_amount' => (float) $items->where('payment_status', 'paid')->sum('instalment_amount'),
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
            'data' => $paginated,
            'meta' => ['current_page' => $page, 'last_page' => (int) ceil($grouped->count() / $perPage), 'per_page' => $perPage, 'total' => $grouped->count()],
        ]);
    }

    public function merchantCreditHistory(Request $request, CreditAssessmentService $creditAssessmentService, RiskAnalyticsService $riskAnalyticsService): JsonResponse
    {
        $query = Customer::with('user');
        if ($request->filled('from')) {
            $query->whereHas('user', fn ($q) => $q->whereDate('created_at', '>=', $request->from));
        }
        if ($request->filled('to')) {
            $query->whereHas('user', fn ($q) => $q->whereDate('created_at', '<=', $request->to));
        }
        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $customers = $query->paginate($perPage);

        $data = $customers->map(function ($customer) use ($creditAssessmentService, $riskAnalyticsService) {
            try {
                $user = $customer->user;
                $creditScoreService = $creditAssessmentService->assess($user->id);
                $riskScoreService = $riskAnalyticsService->calculateForUser($user);

                $creditScore = $creditScoreService['creditScore']['compositeScore'] ?? 0;
                $riskScore = $riskScoreService->total_score ?? 0;
                $finalScore = $creditScore * ($riskScore / 100);
                $newCreditLimit = 20000 * ($finalScore / 100);
                $orders = $user->orders()->where('delivery_status', 'delivered')->get();
                $utilizedAmount = $this->calculateTotalOrderAmount($orders);
                $repaymentRate = $this->calculateRepaymentRate($user->id);

                return [
                    'customer_id' => $customer->id,
                    'name' => $user->name,
                    'credit_limit' => round($newCreditLimit, 2),
                    'utilized_amount' => round($utilizedAmount, 2),
                    'repayment_rate' => round($repaymentRate, 2),
                    'score_change' => round($finalScore, 2),
                ];
            } catch (\Throwable $e) {
                return ['customer_id' => $customer->id, 'name' => '-', 'credit_limit' => 0, 'utilized_amount' => 0, 'repayment_rate' => 0, 'score_change' => 0];
            }
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function onboardingFunnel(Request $request): JsonResponse
    {
        $dateRange = $request->input('date_range', '1M');

        return response()->json([
            'success' => true,
            'data' => [
                'funnel' => [
                    'applications' => 0,
                    'verified' => 0,
                    'approved' => 0,
                    'kyc_passed' => 0,
                    'conversion_rate' => 0,
                ],
                'date_range' => $dateRange,
            ],
        ]);
    }

    private function calculateTotalOrderAmount($orders): float
    {
        $total = 0;
        foreach ($orders as $order) {
            $items = map_product_details($order->product_details);
            $subTotal = (float) $items->sum('total');
            $shipping = (float) ($order->shipping_cost ?? 0);
            $discount = (float) ($order->coupon_discount ?? 0);
            $tax = (float) calculate_order_tax($order);
            $base = $subTotal + $tax + $shipping - $discount;
            $commissionPct = (float) get_system_commission();
            $commissionAmount = $base * ($commissionPct / 100);
            $commissionTaxPct = (float) get_commission_tax();
            $commissionTaxAmt = $commissionAmount * ($commissionTaxPct / 100);
            $total += $base + $commissionAmount + $commissionTaxAmt;
        }

        return $total;
    }

    private function calculateRepaymentRate($userId): float
    {
        $total = SchedulePayment::where('user_id', $userId)->count();
        $paid = SchedulePayment::where('user_id', $userId)->where('payment_status', 'paid')->count();

        return $total > 0 ? ($paid / $total) * 100 : 0;
    }
}
