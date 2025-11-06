<?php

namespace App\Services;

use App\Models\SchedulePayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PortfolioReportService
{
    public function generate($from = null, $to = null)
    {
        session(['portfolioPerformanceFrom' => $from, 'portfolioPerformanceTo' => $to]);
        $months = collect();
        $currentMonth = Carbon::now()->startOfMonth();

        for ($i = 11; $i >= 0; $i--) {
            $month = $currentMonth->copy()->subMonths($i);
            $sum = DB::table('customer_credit_limits')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('limit_arabianpay_after');

            $months->push([
                'label' => $month->format('M'),
                'total' => $sum
            ]);
        }

        $monthly_labels = $months->pluck('label')->toArray();
        $monthly_credits = $months->pluck('total')->toArray();

        //**************************************************** */
        $currentMontH = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        //**************** For Current Month ***************** */
        $currentQuery = DB::table('schedule_payments')
            ->whereYear('due_date', $currentMontH->year)
            ->whereMonth('due_date', $currentMontH->month);

        $totalDueCurrent = $currentQuery->sum('instalment_amount');
        $totalPaidCurrent = (clone $currentQuery)->where('payment_status', 'paid')->sum('deducted_amount');
        $avgDPDCurrent = (clone $currentQuery)->where('late_days', '>', 0)->avg('late_days');
        $totalInstallmentsCurrent = (clone $currentQuery)->count();
        $nplInstallmentsCurrent = (clone $currentQuery)->where('late_days', '>', 90)->count();

        //**************** For Previous Month ***************** */
        $previousQuery = DB::table('schedule_payments')
            ->whereYear('due_date', $previousMonth->year)
            ->whereMonth('due_date', $previousMonth->month);

        $totalDuePrevious = $previousQuery->sum('instalment_amount');
        $totalPaidPrevious = (clone $previousQuery)->where('payment_status', 'paid')->sum('deducted_amount');
        $avgDPDPrevious = (clone $previousQuery)->where('late_days', '>', 0)->avg('late_days');
        $totalInstallmentsPrevious = (clone $previousQuery)->count();
        $nplInstallmentsPrevious = (clone $previousQuery)->where('late_days', '>', 90)->count();

        // Calculate the current month values
        $currentRepaymentRate = $totalDueCurrent > 0 ? round(($totalPaidCurrent / $totalDueCurrent) * 100, 2) : 0;
        $currentAvgDPD = round($avgDPDCurrent ?? 0, 2);
        $currentNPLRatio = $totalInstallmentsCurrent > 0 ? round(($nplInstallmentsCurrent / $totalInstallmentsCurrent) * 100, 2) : 0;

        $previousRepaymentRate = $totalDuePrevious > 0 ? round(($totalPaidPrevious / $totalDuePrevious) * 100, 2) : 0;
        $previousAvgDPD = round($avgDPDPrevious ?? 0, 2);
        $previousNPLRatio = $totalInstallmentsPrevious > 0 ? round(($nplInstallmentsPrevious / $totalInstallmentsPrevious) * 100, 2) : 0;

        //**************************************************** */

        $queryDate = function ($query) use ($from, $to) {
            if ($from && $to) {
                $query->whereBetween('due_date', [$from, $to]);
            }
        };

        $queryDateCreate = function ($query) use ($from, $to) {
            if ($from && $to) {
                $query->whereBetween('created_at', [$from, $to]);
            }
        };

        // Total Credit
        $totalCredit = DB::table('customer_credit_limits')
            ->when($from && $to, $queryDateCreate)
            ->sum('limit_arabianpay_after');

        // Utilized
        $utilizedAmount = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->where('payment_status', '!=', 'unpaid')
            ->sum('principle_amount');

        // Repayment
        $totalDue = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->sum('instalment_amount');

        $totalPaid = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->where('payment_status', 'paid')
            ->sum('deducted_amount');

        // DPD
        $avgDPD = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->where('late_days', '>', 0)
            ->avg('late_days');

        // NPL
        $totalInstallments = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->count();

        $nplInstallments = DB::table('schedule_payments')
            ->when($from && $to, $queryDate)
            ->where('late_days', '>', 90)
            ->count();
        $utilizedPercent = $totalCredit > 0 ? round(($utilizedAmount / $totalCredit) * 100, 2) : 0;
        $unusedPercent = 100 - $utilizedPercent;

        if ($previousAvgDPD > 0) {
            $dpdChange = round((($currentAvgDPD - $previousAvgDPD) / $previousAvgDPD) * 100, 2);
        } else {
            // إذا الشهر السابق = 0 نعتبرها زيادة كاملة 100% أو يمكنك اختيار القيمة المناسبة
            $dpdChange = $currentAvgDPD > 0 ? 100 : 0;
        }

        $topLateCustomers = SchedulePayment::with('user')
            ->select('user_id', DB::raw('SUM(late_days) as total_late_days'))
            ->groupBy('user_id')
            ->orderByDesc('total_late_days')
            ->when($from && $to, $queryDate)
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'first_name' => $item->user->first_name ?? '',
                    'last_name' => $item->user->last_name ?? '',
                    'user_id' => $item->user_id,
                    'total_late_days' => $item->total_late_days,
                ];
            });

        $topActiveCustomers = SchedulePayment::with('user')
            ->select('user_id', DB::raw('COUNT(*) as transactions_count'))
            ->groupBy('user_id')
            ->orderByDesc('transactions_count')
            ->when($from && $to, $queryDate)
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'first_name' => $item->user->first_name ?? '',
                    'last_name' => $item->user->last_name ?? '',
                    'user_id' => $item->user_id,
                    'transactions_count' => $item->transactions_count,
                ];
            });

        $topDueCustomers = SchedulePayment::with('user')
            ->select('user_id', DB::raw('SUM(instalment_amount) as total_due'))
            ->where('payment_status', '!=', 'paid')
            ->groupBy('user_id')
            ->orderByDesc('total_due')
            ->when($from && $to, $queryDate)
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'first_name' => $item->user->first_name ?? '',
                    'last_name' => $item->user->last_name ?? '',
                    'user_id' => $item->user_id,
                    'total_due' => $item->total_due,
                ];
            });

        $payments = SchedulePayment::when($from && $to, $queryDate)
            ->get();
        return [
            'total_credit_issued' => $totalCredit,
            'utilized_amount' => $utilizedAmount,
            'unused_amount' => $totalCredit - $utilizedAmount,
            'utilized_percent' => $utilizedPercent,
            'unused_percent' =>  $unusedPercent,
            'current_repayment_rate' => $currentRepaymentRate,
            'current_average_dpd' => $currentAvgDPD,
            'current_npl_ratio' => $currentNPLRatio,

            'previous_repayment_rate' => $currentRepaymentRate - $previousRepaymentRate,
            'previous_average_dpd' => $currentAvgDPD - $previousAvgDPD,
            'previous_npl_ratio' => $currentNPLRatio - $previousNPLRatio,

            'top_late_customers' => $topLateCustomers,
            'top_active_customers' => $topActiveCustomers,
            'top_due_customers' => $topDueCustomers,

            'avg_dpd_change' => $dpdChange,

            'monthly_credits' => $monthly_credits,
            'monthly_labels' => $monthly_labels,

            'payments' => $payments,
        ];
    }

    public function generatePdf($from = null, $to = null)
    {
        $queryDate = function ($query) use ($from, $to) {
            if ($from && $to) {
                $query->whereBetween('due_date', [$from, $to]);
            }
        };
        $payments = SchedulePayment::when($from && $to, $queryDate)
            ->get();
        return $payments;
    }
}
