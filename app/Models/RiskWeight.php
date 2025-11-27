<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskWeight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'lps_weight',
        'chs_weight',
        'bcs_weight',
        'bps_weight',
        'bes_weight',
        'caf_weight',
        'lps_age_weight',
        'lps_cr_weight',
        'lps_doc_weight',
        'bcs_turnover_weight',
        'bcs_volatility_weight',
        'bcs_returned_weight',
        'bcs_balance_weight',
        'bps_sector_weight',
        'bps_region_weight',
        'bes_dpd_weight',
        'bes_utilization_weight',
        'bes_dispute_weight',
        'bes_trend_weight',
        'last_weight',
        'new_weight'
    ];

    protected $casts = [
        'last_weight' => 'array',
        'new_weight' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Default weights
    public static function getDefaultWeights()
    {
        return [
            'lps_weight' => 15,
            'chs_weight' => 25,
            'bcs_weight' => 20,
            'bps_weight' => 10,
            'bes_weight' => 30,
            'caf_weight' => 0,
            'lps_age_weight' => 40,
            'lps_cr_weight' => 30,
            'lps_doc_weight' => 30,
            'bcs_turnover_weight' => 35,
            'bcs_volatility_weight' => 25,
            'bcs_returned_weight' => 25,
            'bcs_balance_weight' => 15,
            'bps_sector_weight' => 70,
            'bps_region_weight' => 30,
            'bes_dpd_weight' => 40,
            'bes_utilization_weight' => 25,
            'bes_dispute_weight' => 25,
            'bes_trend_weight' => 10,
        ];
    }
}
