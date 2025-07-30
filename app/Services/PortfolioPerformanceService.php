<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Models\SchedulePayment;
use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use Carbon\Carbon;

class PortfolioPerformanceService
{
    protected CreditAssessmentService $creditAssessmentService;
    protected RiskAnalyticsService $riskAnalyticsService;

    public function __construct(
        CreditAssessmentService $creditAssessmentService,
        RiskAnalyticsService $riskAnalyticsService
    ) {
        $this->creditAssessmentService = $creditAssessmentService;
        $this->riskAnalyticsService = $riskAnalyticsService;
    }

    public function getReport(array $filters = []): array
    {
        $utilizedAmount = $this->calculateOrderAmount('completed', $filters);
        $totalIssued    = $this->calculateTotalCreditLimitFromUsers();
        $unusedAmount   = max(0, $totalIssued - $utilizedAmount);

        $utilizedPercent = $totalIssued > 0 ? round(($utilizedAmount / $totalIssued) * 100, 2) : 0;
        $unusedPercent   = $totalIssued > 0 ? round(($unusedAmount / $totalIssued) * 100, 2) : 0;

        $currentFrom = $filters['from'] ?? Carbon::now()->startOfMonth()->toDateString();
        $currentTo   = $filters['to'] ?? Carbon::now()->endOfMonth()->toDateString();

        $previousFrom = Carbon::parse($currentFrom)->subMonth()->startOfMonth()->toDateString();
        $previousTo   = Carbon::parse($currentTo)->subMonth()->endOfMonth()->toDateString();

        $currentRepaymentRate = $this->calculateRepaymentRate($currentFrom, $currentTo, $filters);
        $previousRepaymentRate = $this->calculateRepaymentRate($previousFrom, $previousTo, $filters);

        $currentAverageDpd = $this->calculateAverageDPD($currentFrom, $currentTo, $filters);
        $previousAverageDpd = $this->calculateAverageDPD($previousFrom, $previousTo, $filters);

        $currentNplRatio = $this->calculateNplRatio($currentFrom, $currentTo, $filters);
        $previousNplRatio = $this->calculateNplRatio($previousFrom, $previousTo, $filters);

        $monthlyData = $this->getMonthlyCredits($filters);

        return [
            'total_credit_issued'      => round($totalIssued, 2),
            'utilized_amount'          => round($utilizedAmount, 2),
            'unused_amount'            => round($unusedAmount, 2),
            'utilized_percent'         => $utilizedPercent,
            'unused_percent'           => $unusedPercent,

            'current_repayment_rate'   => $currentRepaymentRate,
            'previous_repayment_rate'  => $previousRepaymentRate,

            'current_average_dpd'      => $currentAverageDpd,
            'avg_dpd_change'           => round($currentAverageDpd - $previousAverageDpd, 2),

            'current_npl_ratio'        => $currentNplRatio,
            'previous_npl_ratio'       => $previousNplRatio,

            'monthly_credits'          => $monthlyData['credits'],
            'monthly_labels'           => $monthlyData['labels'],
        ];
    }

    private function calculateRepaymentRate(string $from, string $to, array $filters = []): float
    {
        $query = SchedulePayment::whereBetween('due_date', [$from, $to]);
        if (!empty($filters['merchant_id'])) {
            $query->where('seller_id', $filters['merchant_id']);
        }
        $scheduled = (clone $query)->sum('instalment_amount');

        $paidQuery = (clone $query)->where('payment_status', 'paid');
        $paid = $paidQuery->sum('instalment_amount');

        return $scheduled > 0 ? round(($paid / $scheduled) * 100, 2) : 0;
    }

    private function calculateAverageDPD(string $from, string $to, array $filters = []): float
    {
        $query = SchedulePayment::whereBetween('due_date', [$from, $to])
            ->where('is_late', true)
            ->where('payment_status', 'paid');

        if (!empty($filters['merchant_id'])) {
            $query->where('seller_id', $filters['merchant_id']);
        }

        $payments = $query->get();

        if ($payments->isEmpty()) {
            return 0;
        }

        $totalDpd = $payments->sum('late_days');

        return round($totalDpd / $payments->count(), 2);
    }

    private function calculateNplRatio(string $from, string $to, array $filters = []): float
    {
        $query = SchedulePayment::whereBetween('due_date', [$from, $to]);
        if (!empty($filters['merchant_id'])) {
            $query->where('seller_id', $filters['merchant_id']);
        }

        $payments = $query->get();

        $total = $payments->count();
        if ($total === 0) {
            return 0;
        }

        $npl = $payments->filter(function ($payment) {
            return $payment->payment_status !== 'paid' &&
                Carbon::parse($payment->due_date)->diffInDays(Carbon::now()) > 90;
        })->count();

        return round(($npl / $total) * 100, 2);
    }

    private function calculateOrderAmount(string $status, array $filters = []): float
    {
        $query = Order::where('payment_status', $status);

        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (!empty($filters['merchant_id'])) {
            $query->where('seller_id', $filters['merchant_id']);
        }

        return (float) $query->get()->sum(function ($order) {
            $total = 0;
            $items = map_product_details($order->product_details);

            foreach ($items as $item) {
                if (!$item || !isset($item->total)) {
                    continue;
                }
                $total += $item->total;
            }

            if ($order->late_fee) {
                $total += $order->late_fee;
            }

            return $total * 1.15; // with VAT
        });
    }

    private function calculateTotalCreditLimitFromUsers(): float
    {
        $users = Customer::where('status', 'approved')->get();

        $total = 0;

        foreach ($users as $user) {
            try {
                $creditScoreData = $this->creditAssessmentService->assess($user->user_id);
                $riskScoreData   = $this->riskAnalyticsService->calculateForUser($user->user);

                $creditScore = $creditScoreData['creditScore']['compositeScore'] ?? 0;
                $riskScore   = $riskScoreData->total_score ?? 0;

                $oldCreditLimit = 20000;
                $finalScore     = $creditScore * ($riskScore / 100);
                $newCreditLimit = $oldCreditLimit * ($finalScore / 100);

                $total += $newCreditLimit;
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $total;
    }

    private function getMonthlyCredits(array $filters = []): array
    {
        $credits = [];
        $labels = [];

        for ($i = 0; $i < 12; $i++) {
            $monthStart = Carbon::now()->subMonths(11 - $i)->startOfMonth();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthName = $monthStart->format('M');

            $query = Order::where('payment_status', 'completed')
                ->whereBetween('created_at', [$monthStart, $monthEnd]);

            if (!empty($filters['merchant_id'])) {
                $query->where('seller_id', $filters['merchant_id']);
            }

            $monthlyAmount = $query->get()
                ->sum(function ($order) {
                    $total = 0;
                    $items = map_product_details($order->product_details);

                    foreach ($items as $item) {
                        if (!$item || !isset($item->total)) {
                            continue;
                        }
                        $total += $item->total;
                    }

                    if ($order->late_fee) {
                        $total += $order->late_fee;
                    }

                    return $total * 1.15;
                });

            $credits[] = round($monthlyAmount, 2);
            $labels[] = $monthName;
        }

        return ['credits' => $credits, 'labels' => $labels];
    }
}
