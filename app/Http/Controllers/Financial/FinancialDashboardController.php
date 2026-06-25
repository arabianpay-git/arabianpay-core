<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FAccounts;
use App\Models\FEntry;
use App\Models\FTransaction;
use App\Models\InvestmentPool;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialDashboardController extends Controller
{
    public function index(Request $request)
    {

        $data = [
            'kpis' => $this->getKPIs(),
            'chartData' => $this->getChartData(),
            'recentActivities' => $this->getRecentActivities(),
            'accountsSummary' => $this->getAccountsSummary(),
            'trialBalance' => $this->trialBalance($request),
            'investmentPools' => $this->getInvestmentPoolsSummary(),
        ];

        return view('admin.financial.dashboard', $data);
    }

    private function getKPIs()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();
        $previousMonth = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Total Assets vs Liabilities
        $totalAssets = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 1); // 1 for Assets
        })->sum('debit') - FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 1);
        })->sum('credit');

        $totalLiabilities = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 2); // 1 for Liabilities
        })->sum('credit') - FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 2);
        })->sum('debit');

        // Previous month net worth for comparison
        $prevAssets = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 1);
        })->where('created_at', '<', $currentMonth)->sum('debit') - FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 1);
        })->where('created_at', '<', $currentMonth)->sum('credit');

        $prevLiabilities = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 2);
        })->where('created_at', '<', $currentMonth)->sum('credit') - FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 1)->where('account_type2', 2);
        })->where('created_at', '<', $currentMonth)->sum('debit');

        // Current Month Revenue vs Expenses
        $monthlyRevenue = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 2)->where('account_type2', 2);
        })->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('credit') - FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 2);
            })->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('debit');

        $monthlyExpenses = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 2)->where('account_type2', 1);
        })->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('debit') - FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 1);
            })->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->sum('credit');

        // Previous Month Revenue vs Expenses
        $prevRevenue = FEntry::whereHas('account', function ($query) {
            $query->where('account_type1', 2)->where('account_type2', 2);
        })->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('credit') - FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 2);
            })->whereBetween('created_at', [$previousMonth, $previousMonthEnd])
            ->sum('debit');

        // Cash Flow Status (Assets - Liabilities)
        $cashFlow = $totalAssets - $totalLiabilities;

        // Accounts receivable/payable could be added here as well
        $accountsReceivable = FEntry::whereHas('account', function ($query) {
            $query->where('id', 1203); // Assuming 1203 is Accounts Receivable
        })->sum('debit') - FEntry::whereHas('account', function ($query) {
            $query->where('id', 1203);
        })->sum('credit');

        $prevAccountsReceivable = FEntry::whereHas('account', function ($query) {
            $query->where('id', 1203);
        })->where('created_at', '<', $currentMonth)->sum('debit') - FEntry::whereHas('account', function ($query) {
            $query->where('id', 1203);
        })->where('created_at', '<', $currentMonth)->sum('credit');

        $accountsPayable = FEntry::whereHas('account', function ($query) {
            $query->where('id', 2400); // Assuming 2400 is Accounts Payable
        })->sum('credit') - FEntry::whereHas('account', function ($query) {
            $query->where('id', 2400);
        })->sum('debit');

        $prevAccountsPayable = FEntry::whereHas('account', function ($query) {
            $query->where('id', 2400);
        })->where('created_at', '<', $currentMonth)->sum('credit') - FEntry::whereHas('account', function ($query) {
            $query->where('id', 2400);
        })->where('created_at', '<', $currentMonth)->sum('debit');

        $liquidity = FEntry::whereHas('account', function ($query) {
            $query->where('id', 1201); // Assuming 1201 is cash on the bank and wallet
        })->sum('credit') - FEntry::whereHas('account', function ($query) {
            $query->where('id', 1201);
        })->sum('debit');

        // Calculate trends
        $netWorth = $totalAssets - $totalLiabilities;
        $prevNetWorth = $prevAssets - $prevLiabilities;
        $netIncome = $monthlyRevenue - $monthlyExpenses;
        $prevNetIncome = $prevRevenue;

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
            // Trends
            'net_worth_trend' => $this->calculateTrend($netWorth, $prevNetWorth),
            'net_income_trend' => $this->calculateTrend($netIncome, $prevNetIncome),
            'accounts_receivable_trend' => $this->calculateTrend($accountsReceivable, $prevAccountsReceivable),
            'accounts_payable_trend' => $this->calculateTrend($accountsPayable, $prevAccountsPayable),
            // Sparkline data (last 6 months)
            'net_worth_sparkline' => $this->getNetWorthSparkline(),
            'net_income_sparkline' => $this->getNetIncomeSparkline(),
            'accounts_receivable_sparkline' => $this->getAccountsReceivableSparkline(),
            'accounts_payable_sparkline' => $this->getAccountsPayableSparkline(),
        ];
    }

    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    private function getNetWorthSparkline()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();

            $assets = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 1)->where('account_type2', 1);
            })->where('created_at', '<=', $monthEnd)->sum('debit') - FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 1)->where('account_type2', 1);
            })->where('created_at', '<=', $monthEnd)->sum('credit');

            $liabilities = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 1)->where('account_type2', 2);
            })->where('created_at', '<=', $monthEnd)->sum('credit') - FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 1)->where('account_type2', 2);
            })->where('created_at', '<=', $monthEnd)->sum('debit');

            $data[] = $assets - $liabilities;
        }

        return $data;
    }

    private function getNetIncomeSparkline()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 2);
            })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit') - FEntry::whereHas('account', function ($query) {
                    $query->where('account_type1', 2)->where('account_type2', 2);
                })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit');

            $expenses = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 1);
            })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit') - FEntry::whereHas('account', function ($query) {
                    $query->where('account_type1', 2)->where('account_type2', 1);
                })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit');

            $data[] = $revenue - $expenses;
        }

        return $data;
    }

    private function getAccountsReceivableSparkline()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();

            $value = FEntry::whereHas('account', function ($query) {
                $query->where('id', 1203);
            })->where('created_at', '<=', $monthEnd)->sum('debit') - FEntry::whereHas('account', function ($query) {
                $query->where('id', 1203);
            })->where('created_at', '<=', $monthEnd)->sum('credit');

            $data[] = $value;
        }

        return $data;
    }

    private function getAccountsPayableSparkline()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth();

            $value = FEntry::whereHas('account', function ($query) {
                $query->where('id', 2400);
            })->where('created_at', '<=', $monthEnd)->sum('credit') - FEntry::whereHas('account', function ($query) {
                $query->where('id', 2400);
            })->where('created_at', '<=', $monthEnd)->sum('debit');

            $data[] = $value;
        }

        return $data;
    }

    private function getChartData()
    {
        // Monthly Cash Flow for the last 6 months
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $revenue = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 2);
            })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit') - FEntry::whereHas('account', function ($query) {
                    $query->where('account_type1', 2)->where('account_type2', 2);
                })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit');

            $expenses = FEntry::whereHas('account', function ($query) {
                $query->where('account_type1', 2)->where('account_type2', 1);
            })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('debit') - FEntry::whereHas('account', function ($query) {
                    $query->where('account_type1', 2)->where('account_type2', 1);
                })->whereBetween('created_at', [$monthStart, $monthEnd])
                ->sum('credit');

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net' => $revenue - $expenses,
            ];
        }

        // Account Balances by Type
        $accountTypes = FAccounts::select('account_type1', DB::raw('count(*) as count'))
            ->groupBy('account_type1')
            ->get();

        return [
            'monthly_data' => $monthlyData,
            'account_types' => $accountTypes,
        ];
    }

    private function getRecentActivities()
    {
        return FTransaction::with(['user', 'entries.account'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getAccountsSummary()
    {
        return FAccounts::select(
            'account_type1',
            DB::raw('count(*) as count'),
            DB::raw('SUM(CASE WHEN account_type1 IN ("Assets", "Expenses") THEN 
                (SELECT COALESCE(SUM(debit) - SUM(credit), 0) FROM f_entries WHERE account_id = f_accounts.id)
                ELSE 
                (SELECT COALESCE(SUM(credit) - SUM(debit), 0) FROM f_entries WHERE account_id = f_accounts.id)
                END) as total_balance')
        )
            ->groupBy('account_type1')
            ->get();
    }

    public function getChartDataJson(Request $request)
    {
        $type = $request->get('type', 'monthly');

        switch ($type) {
            case 'monthly':
                return response()->json($this->getChartData()['monthly_data']);
            case 'accounts':
                return response()->json($this->getChartData()['account_types']);
            default:
                return response()->json([]);
        }
    }

    public function trialBalance(Request $request)
    {
        $data = [
            'trialBalance' => $this->getTrialBalance($request),
            'balanceDate' => $request->get('date', Carbon::now()->format(dateFormat())),
            'totalDebits' => 0,
            'totalCredits' => 0,
        ];

        // Calculate totals for validation
        foreach ($data['trialBalance'] as $account) {
            $data['totalDebits'] += $account->total_debit;
            $data['totalCredits'] += $account->total_credit;
        }

        return $data;
    }

    private function getTrialBalance($request = null)
    {
        $date = $request ? $request->get('date', Carbon::now()->format(dateFormat())) : Carbon::now()->format(dateFormat());

        // Get all accounts with their balances up to the specified date
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
            //  ->where(function($query) use ($date) {
            //      $query->where('f_entries.entry_date', '<=', $date)
            //           ->orWhereNull('f_entries.entry_date');
            //  })
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

    public function exportTrialBalance(Request $request)
    {
        $trialBalance = $this->getTrialBalance($request);
        $date = $request->get('date', Carbon::now()->format(dateFormat()));

        $filename = 'trial_balance_'.$date.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($trialBalance, $date) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, ['Trial Balance as of '.Carbon::parse($date)->format('F d, Y')]);
            fputcsv($file, []);
            fputcsv($file, ['Account Code', 'Account Name', 'Account Type', 'Debit', 'Credit', 'Balance']);

            $totalDebits = 0;
            $totalCredits = 0;

            foreach ($trialBalance as $account) {
                $accountType = $account->account_type1 == 1 ? 'Budget' : 'Non-Budget';
                $accountType .= ' - '.($account->account_type2 == 1 ? 'Debit' : 'Credit');

                fputcsv($file, [
                    $account->account_code,
                    $account->account_name,
                    $accountType,
                    number_format($account->total_debit, 2),
                    number_format($account->total_credit, 2),
                    number_format($account->balance, 2),
                ]);

                $totalDebits += $account->total_debit;
                $totalCredits += $account->total_credit;
            }

            // Add totals
            fputcsv($file, []);
            fputcsv($file, ['TOTAL', '', '', number_format($totalDebits, 2), number_format($totalCredits, 2), '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getInvestmentPoolsSummary()
    {
        try {
            // Get last 3 pools ordered by start date
            $recentPools = InvestmentPool::orderBy('start_date', 'desc')
                ->limit(3)
                ->get();

            // Overall summary
            $totalPools = InvestmentPool::count();
            $activePools = InvestmentPool::where('status', 'active')->count();
            $totalDisbursed = InvestmentPool::sum('total_disbursed');
            $totalCollected = InvestmentPool::sum('total_collected');
            $avgCollectionRate = InvestmentPool::avg('collection_rate') ?: 0;

            return [
                'recent_pools' => $recentPools->map(function ($pool) {
                    return [
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
                    ];
                }),
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
            // Return empty data if pools don't exist yet
            return [
                'recent_pools' => collect([]),
                'summary' => [
                    'total_pools' => 0,
                    'active_pools' => 0,
                    'total_disbursed' => 0,
                    'total_collected' => 0,
                    'avg_collection_rate' => 0,
                    'total_outstanding' => 0,
                ],
            ];
        }
    }
}
