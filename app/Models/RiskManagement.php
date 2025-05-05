<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RiskManagement extends Model
{

    /**
     * Risk score bands distribution.
     */
    public static function getRiskScoreDistribution()
    {
        return DB::table('risk_management')
            ->select('risk_level as band', DB::raw('COUNT(*) as count'))
            ->groupBy('risk_level')
            ->get();
    }

    /**
     * Default rates by business type for heatmap.
     */
    public static function getDefaultRatesByBusiness()
    {
        $data = DB::table('risk_management')
            ->select('business_type as line', 'default_count')
            ->get()
            ->groupBy('line');

        return $data->map(fn($items, $line) => [
            'name' => $line,
            'data' => $items->pluck('default_count')->toArray(),
        ])->values()->toArray();
    }

    /**
     * Average credit score.
     */
    public static function getAverageCreditScores()
    {
        return (float) DB::table('risk_management')
            ->avg('credit_score');
    }
}
