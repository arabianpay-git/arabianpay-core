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
        'last_weight',
        'new_weight',

        // Main weights
        'cr_id',
        'pos',
        'repayment',
        'industry',
        'location',

        // CR / ID sub-weights
        'cr_id_sub_id_match',
        'cr_id_sub_id_expiry',
        'cr_id_sub_cr_expiry',
        'cr_id_sub_industry',
        'cr_id_sub_activity',
        'cr_id_sub_total',

        // POS
        'pos_threshold',

        // Repayment
        'repayment_few_threshold',
        'repayment_score_no_delays',
        'repayment_score_few_delays',
        'repayment_score_many_delays',

        // Location
        'location_activity_max',
        'location_default_rate_max',
        'location_sub_total_max',
    ];

    protected $casts = [
        'last_weight' => 'array',
        'new_weight' => 'array',
    ];

    // Relation to creator user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation to employee who last updated the weights
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
