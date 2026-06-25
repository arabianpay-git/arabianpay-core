<?php

namespace App\Models;

use App\Traits\WithApprovalContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    use WithApprovalContext;

    protected $fillable = [
        'uuid',
        'assigned_to',
        'refrence_payment',
        'user_id',
        'seller_id',
        'order_id',
        'product_ids',
        'plan_id',
        'collected',
        'retrieved',
        'canceled',
        'loan_amount',
        'loan_start_date',
        'loan_end_date',
        'loan_term',
        'subscription_fees',
        'credit_limit_at_time',
        'remaining_credit_limit',
        'payment_status',
        'settlement_status',
        'general_status',
        'resource',
    ];

    protected $casts = [
        'product_ids' => 'array',
        'loan_amount' => 'decimal:2',
        'collected' => 'decimal:2',
        'retrieved' => 'decimal:2',
        'canceled' => 'decimal:2',
        'subscription_fees' => 'decimal:2',
        'credit_limit_at_time' => 'decimal:2',
        'remaining_credit_limit' => 'decimal:2',
    ];

    /**
     * Boot method to generate UUID.
     */
    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->uuid)) {
                $transaction->uuid = (string) Str::uuid();
            }
        });
    }

    // Relations
    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function plan()
    {
        return $this->belongsTo(InstalmentPlan::class, 'plan_id');
    }

    public function schedulePayments()
    {
        return $this->hasMany(SchedulePayment::class, 'order_id', 'order_id');
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'order_id', 'order_id')
            ->where('seller_id', Auth::id());
    }

    public function refundRequests()
    {
        return $this->hasMany(RefundRequest::class, 'order_id', 'order_id')
            ->where('seller_id', $this->seller_id);
    }

    public function getRefundAmountAttribute()
    {
        // this requires you eager‐load `refund_requests_sum`
        return $this->refund_requests_sum ?? 0;
    }

    /**
     * Disbursed vs repaid over past N months.
     */
    public static function getLoanFlowData($range)
    {
        $months = (int) $range;
        $end = Carbon::now();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        $disbursed = DB::table('transactions')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('SUM(loan_amount) as total'))
            ->where('loan_amount', '>', 0)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $repaid = DB::table('transactions')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('SUM(collected) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $d = $r = [];
        foreach ($labels as $m) {
            $d[] = $disbursed[$m] ?? 0;
            $r[] = $repaid[$m] ?? 0;
        }

        return ['months' => $labels, 'disbursed' => $d, 'repaid' => $r];
    }

    /**
     * Cash flow (collected vs canceled) over N months.
     */
    public static function getCashFlowData($range)
    {
        $months = (int) $range;
        $end = Carbon::now();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        $repaid = DB::table('transactions')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('SUM(collected) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $canceled = DB::table('transactions')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('SUM(canceled) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $inflows = $outflows = [];
        foreach ($labels as $m) {
            $inflows[] = $repaid[$m] ?? 0;
            $outflows[] = $canceled[$m] ?? 0;
        }

        return ['months' => $labels, 'inflows' => $inflows, 'outflows' => $outflows];
    }

    /**
     * Count by settlement_status.
     */
    public static function getSettlementStatus()
    {
        return DB::table('transactions')
            ->select('settlement_status', DB::raw('COUNT(*) as count'))
            ->groupBy('settlement_status')
            ->get();
    }

    /**
     * Risk exposure delegate.
     */
    public static function getRiskExposureData()
    {
        return RiskManagement::getDefaultRatesByBusiness();
    }
}
