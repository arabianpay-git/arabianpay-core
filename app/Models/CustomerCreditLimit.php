<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CustomerCreditLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'package_id',
        'simah_limit',
        'limit_arabianpay_after',
        'report_simah',
    ];

    protected $casts = [
        'simah_limit' => 'decimal:2',
        'limit_arabianpay_after' => 'decimal:2',
        'report_simah' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
















    /**
     * Total vs used ArabianPay credit utilization.
     */
    public static function getCreditUtilization()
    {
        // use your limit_arabianpay_before/after if needed
        $totalLimit = DB::table('customer_credit_limits')->sum('simah_limit');
        $used       = DB::table('customer_credit_limits')->sum(DB::raw('limit_arabianpay_before - limit_arabianpay_after'));

        return [
            'limit'               => $totalLimit,
            'used'                => $used,
            'utilization_percent' => $totalLimit ? round($used / $totalLimit * 100, 1) : 0,
        ];
    }
}
