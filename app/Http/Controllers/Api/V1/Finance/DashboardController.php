<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancialDashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected FinancialDashboardService $dashboardService
    ) {}

    public function index(): JsonResponse
    {
        $kpis = $this->dashboardService->getKPIs();
        $chartData = $this->dashboardService->getChartData();
        $recentActivities = $this->dashboardService->getRecentActivities();

        return response()->json([
            'success' => true,
            'data' => [
                'kpis' => $kpis,
                'charts' => $chartData,
                'recent_activities' => $recentActivities,
                'accounts_summary' => $this->dashboardService->getAccountsSummary(),
                'investment_pools' => $this->dashboardService->getInvestmentPoolsSummary(),
            ],
        ]);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $trialBalance = $this->dashboardService->getTrialBalance($request);
        $balanceDate = $request->get('date', Carbon::now()->format(dateFormat()));
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($trialBalance as $account) {
            $totalDebits += $account->total_debit;
            $totalCredits += $account->total_credit;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'accounts' => $trialBalance,
                'balance_date' => $balanceDate,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
            ],
        ]);
    }

    public function exportTrialBalance(Request $request)
    {
        $trialBalance = $this->dashboardService->getTrialBalance($request);
        $date = $request->get('date', Carbon::now()->format(dateFormat()));

        $filename = 'trial_balance_'.$date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($trialBalance, $date) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Trial Balance as of '.Carbon::parse($date)->format('F d, Y')]);
            fputcsv($file, []);
            fputcsv($file, ['Account Code', 'Account Name', 'Account Type', 'Debit', 'Credit', 'Balance']);

            $totalDebits = 0;
            $totalCredits = 0;

            foreach ($trialBalance as $account) {
                $accountType = $account->account_type1 == 1 ? 'Budget' : 'Non-Budget';
                $accountType .= ' - '.($account->account_type2 == 1 ? 'Debit' : 'Credit');

                fputcsv($file, [
                    $account->id,
                    $account->account_name,
                    $accountType,
                    number_format($account->total_debit, 2),
                    number_format($account->total_credit, 2),
                    number_format($account->balance, 2),
                ]);

                $totalDebits += $account->total_debit;
                $totalCredits += $account->total_credit;
            }

            fputcsv($file, []);
            fputcsv($file, ['TOTAL', '', '', number_format($totalDebits, 2), number_format($totalCredits, 2), '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
