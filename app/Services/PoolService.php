<?php

namespace App\Services;

use App\Models\Checkout;
use App\Models\InvestmentPool;
use Carbon\Carbon;

class PoolService
{
    /**
     * Create a new investment pool for the given month
     */
    public function createMonthlyPool($year, $month)
    {
        // Check if pool already exists for this month
        $existingPool = InvestmentPool::byMonth($year, $month)->first();

        if ($existingPool) {
            return $existingPool;
        }

        return InvestmentPool::createMonthlyPool($year, $month);
    }

    /**
     * Assign a checkout to the appropriate investment pool
     */
    public function assignCheckoutToPool(Checkout $checkout)
    {
        $createdAt = $checkout->created_at ?? Carbon::now();
        $year = $createdAt->year;
        $month = $createdAt->month;

        // Get or create pool for this month
        $pool = $this->createMonthlyPool($year, $month);

        // Assign checkout to pool
        $checkout->update(['pool_id' => $pool->id]);

        // Update pool metrics
        $pool->updateMetrics();

        return $pool;
    }

    /**
     * Get current active pools
     */
    public function getActivePools()
    {
        return InvestmentPool::active()->current()->get();
    }

    /**
     * Get pool performance summary
     */
    public function getPoolPerformanceSummary()
    {
        return InvestmentPool::selectRaw('
            COUNT(*) as total_pools,
            SUM(total_disbursed) as total_disbursed,
            SUM(total_collected) as total_collected,
            AVG(collection_rate) as avg_collection_rate,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_pools,
            SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_pools
        ')->first();
    }

    /**
     * Close pools that have passed their end date
     */
    public function closeExpiredPools()
    {
        $expiredPools = InvestmentPool::where('status', 'active')
            ->where('end_date', '<', Carbon::now())
            ->get();

        foreach ($expiredPools as $pool) {
            $pool->closePool();
        }

        return $expiredPools->count();
    }

    /**
     * Get pool analytics for dashboard
     */
    public function getPoolAnalytics()
    {
        $pools = InvestmentPool::with(['checkouts', 'payments'])
            ->orderBy('start_date', 'desc')
            ->limit(12)
            ->get();

        return [
            'monthly_performance' => $pools->map(function ($pool) {
                return [
                    'name' => $pool->name,
                    'month' => $pool->start_date->format('M Y'),
                    'total_disbursed' => $pool->total_disbursed,
                    'total_collected' => $pool->total_collected,
                    'collection_rate' => $pool->collection_rate,
                    'status' => $pool->status,
                    'days_remaining' => $pool->days_remaining,
                ];
            }),
            'summary' => $this->getPoolPerformanceSummary(),
            'risk_analysis' => $this->getRiskAnalysis($pools),
        ];
    }

    /**
     * Get risk analysis for pools
     */
    private function getRiskAnalysis($pools)
    {
        $totalPools = $pools->count();
        $highRiskPools = $pools->where('collection_rate', '<', 80)->count();
        $mediumRiskPools = $pools->where('collection_rate', '>=', 80)
            ->where('collection_rate', '<', 90)->count();
        $lowRiskPools = $pools->where('collection_rate', '>=', 90)->count();

        return [
            'high_risk' => $highRiskPools,
            'medium_risk' => $mediumRiskPools,
            'low_risk' => $lowRiskPools,
            'risk_distribution' => [
                'high' => $totalPools > 0 ? ($highRiskPools / $totalPools) * 100 : 0,
                'medium' => $totalPools > 0 ? ($mediumRiskPools / $totalPools) * 100 : 0,
                'low' => $totalPools > 0 ? ($lowRiskPools / $totalPools) * 100 : 0,
            ],
        ];
    }

    /**
     * Auto-assign existing checkouts to pools (for initial setup)
     */
    public function assignExistingCheckouts()
    {
        $checkouts = Checkout::whereNull('pool_id')->get();
        $assigned = 0;

        foreach ($checkouts as $checkout) {
            $this->assignCheckoutToPool($checkout);
            $assigned++;
        }

        return $assigned;
    }

    /**
     * Get cash flow projections for pools
     */
    public function getCashFlowProjections($months = 6)
    {
        $projections = [];
        $startDate = Carbon::now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $pool = InvestmentPool::byMonth($date->year, $date->month)->first();

            $projections[] = [
                'month' => $date->format('M Y'),
                'expected_disbursed' => $pool ? $pool->total_disbursed : 0,
                'expected_collected' => $pool ? $pool->expected_collections : 0,
                'actual_collected' => $pool ? $pool->total_collected : 0,
                'projection' => $pool ? $pool->expected_collections - $pool->total_collected : 0,
            ];
        }

        return $projections;
    }
}
