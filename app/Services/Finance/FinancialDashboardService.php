<?php

namespace App\Services\Finance;

use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\InvestmentPool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialDashboardService
{
    public function getKPIs(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $totalAssets = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
            ->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
            ->sum('credit');

        $totalLiabilities = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
            ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
            ->sum('debit');

        $prevAssets = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
            ->where('created_at', '<', $currentMonth)->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
            ->where('created_at', '<', $currentMonth)->sum('credit');

        $prevLiabilities = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
            ->where('created_at', '<', $currentMonth)->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
            ->where('created_at', '<', $currentMonth)->sum('debit');

        $monthlyRevenue = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
            ->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
            ->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('debit');

        $monthlyExpenses = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
            ->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
            ->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('credit');

        $prevRevenue = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
            ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
            ->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('debit');

        $cashFlow = $totalAssets - $totalLiabilities;

        $accountsReceivable = FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
            ->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
            ->sum('credit');

        $prevAccountsReceivable = FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
            ->where('created_at', '<', $currentMonth)->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
            ->where('created_at', '<', $currentMonth)->sum('credit');

        $accountsPayable = FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
            ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
            ->sum('debit');

        $prevAccountsPayable = FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
            ->where('created_at', '<', $currentMonth)->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
            ->where('created_at', '<', $currentMonth)->sum('debit');

        $liquidity = FEntry::whereHas('account', fn ($q) => $q->where('id', 1201))
            ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 1201))
            ->sum('debit');

        $netWorth = $totalAssets - $totalLiabilities;
        $prevNetWorth = $prevAssets - $prevLiabilities;
        $netIncome = $monthlyRevenue - $monthlyExpenses;

        return [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenses' => $monthlyExpenses,
            'cash_flow' => $cashFlow,
            'net_income' => $netIncome,
            'accounts_receivable' => $accountsReceivable,
            'accounts_payable' => $accountsPayable,
            'liquidity' => $liquidity,
            'net_worth_trend' => $this->calculateTrend($netWorth, $prevNetWorth),
            'net_income_trend' => $this->calculateTrend($netIncome, $prevRevenue),
            'accounts_receivable_trend' => $this->calculateTrend($accountsReceivable, $prevAccountsReceivable),
            'accounts_payable_trend' => $this->calculateTrend($accountsPayable, $prevAccountsPayable),
            'net_worth_sparkline' => $this->getNetWorthSparkline(),
            'net_income_sparkline' => $this->getNetIncomeSparkline(),
            'accounts_receivable_sparkline' => $this->getAccountsReceivableSparkline(),
            'accounts_payable_sparkline' => $this->getAccountsPayableSparkline(),
        ];
    }

    public function getChartData(): array
    {
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit');

            $expenses = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit');

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net' => $revenue - $expenses,
            ];
        }

        $accountTypes = FAccounts::select('account_type1', DB::raw('count(*) as count'))
            ->groupBy('account_type1')
            ->get();

        return [
            'monthly_data' => $monthlyData,
            'account_types' => $accountTypes,
        ];
    }

    public function getRecentActivities()
    {
        return FTransaction::with(['user', 'entries.account'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getAccountsSummary()
    {
        return FAccounts::select(
            'account_type1',
            DB::raw('count(*) as count'),
            DB::raw('SUM(CASE WHEN account_type2 = 1 THEN 
                (SELECT COALESCE(SUM(debit) - SUM(credit), 0) FROM f_entries WHERE account_id = f_accounts.id)
                ELSE 
                (SELECT COALESCE(SUM(credit) - SUM(debit), 0) FROM f_entries WHERE account_id = f_accounts.id)
                END) as total_balance')
        )
            ->groupBy('account_type1')
            ->get();
    }

    public function getTrialBalance(?Request $request = null)
    {
        $date = $request ? $request->get('date', Carbon::now()->format(dateFormat())) : Carbon::now()->format(dateFormat());

        $accounts = FAccounts::select([
            'f_accounts.id',
            'f_accounts.account_name',
            'f_accounts.account_type1',
            'f_accounts.account_type2',
            DB::raw('COALESCE(SUM(f_entries.debit), 0) as total_debit'),
            DB::raw('COALESCE(SUM(f_entries.credit), 0) as total_credit'),
            DB::raw('CASE 
                WHEN f_accounts.account_type2 = 1 THEN COALESCE(SUM(f_entries.debit), 0) - COALESCE(SUM(f_entries.credit), 0)
                ELSE COALESCE(SUM(f_entries.credit), 0) - COALESCE(SUM(f_entries.debit), 0)
                END as balance'),
        ])
            ->leftJoin('f_entries', 'f_accounts.id', '=', 'f_entries.account_id')
            ->where('f_accounts.status', 'active')
            ->groupBy([
                'f_accounts.id',
                'f_accounts.account_name',
                'f_accounts.account_type1',
                'f_accounts.account_type2',
            ])
            ->orderBy('f_accounts.id')
            ->get();

        return $accounts;
    }

    public function getInvestmentPoolsSummary(): array
    {
        try {
            $recentPools = InvestmentPool::orderBy('start_date', 'desc')
                ->limit(3)
                ->get();

            $totalPools = InvestmentPool::count();
            $activePools = InvestmentPool::where('status', 'active')->count();
            $totalDisbursed = InvestmentPool::sum('total_disbursed');
            $totalCollected = InvestmentPool::sum('total_collected');
            $avgCollectionRate = InvestmentPool::avg('collection_rate') ?: 0;

            return [
                'recent_pools' => $recentPools->map(fn ($pool) => [
                    'id' => $pool->id,
                    'name' => $pool->name,
                    'start_date' => $pool->start_date,
                    'end_date' => $pool->end_date,
                    'status' => $pool->status,
                    'total_disbursed' => $pool->total_disbursed,
                    'total_collected' => $pool->total_collected,
                    'expected_collections' => $pool->expected_collections,
                    'collection_rate' => $pool->collection_rate,
                    'total_checkouts' => $pool->total_checkouts,
                    'days_remaining' => $pool->end_date->diffInDays(now(), false),
                    'is_active' => $pool->status === 'active',
                ])->toArray(),
                'summary' => [
                    'total_pools' => $totalPools,
                    'active_pools' => $activePools,
                    'total_disbursed' => $totalDisbursed,
                    'total_collected' => $totalCollected,
                    'avg_collection_rate' => round($avgCollectionRate, 2),
                    'total_outstanding' => $totalDisbursed - $totalCollected,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'recent_pools' => [],
                'summary' => [
                    'total_pools' => 0, 'active_pools' => 0, 'total_disbursed' => 0,
                    'total_collected' => 0, 'avg_collection_rate' => 0, 'total_outstanding' => 0,
                ],
            ];
        }
    }

    private function calculateTrend($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    private function getNetWorthSparkline(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();
            $assets = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
                ->where('created_at', '<=', $monthEnd)->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 1))
                ->where('created_at', '<=', $monthEnd)->sum('credit');
            $liabilities = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
                ->where('created_at', '<=', $monthEnd)->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 1)->where('account_type2', 2))
                ->where('created_at', '<=', $monthEnd)->sum('debit');
            $data[] = $assets - $liabilities;
        }

        return $data;
    }

    private function getNetIncomeSparkline(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $revenue = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 2))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit');
            $expenses = FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('account_type1', 2)->where('account_type2', 1))
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit');
            $data[] = $revenue - $expenses;
        }

        return $data;
    }

    private function getAccountsReceivableSparkline(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();
            $data[] = FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
                ->where('created_at', '<=', $monthEnd)->sum('debit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 1203))
                ->where('created_at', '<=', $monthEnd)->sum('credit');
        }

        return $data;
    }

    private function getAccountsPayableSparkline(): array
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();
            $data[] = FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
                ->where('created_at', '<=', $monthEnd)->sum('credit') - FEntry::whereHas('account', fn ($q) => $q->where('id', 2400))
                ->where('created_at', '<=', $monthEnd)->sum('debit');
        }

        return $data;
    }
}
