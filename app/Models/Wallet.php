<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'order_id',
        'instalment_id',
        'transaction_type',
        'amount',
        'balance_after',
        'status',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function instalment()
    {
        return $this->belongsTo(InstalmentPlan::class, 'instalment_id');
    }
















    /**
     * Balance trend (average) over N months.
     */
    public static function getBalanceTrends($range)
    {
        $months = (int)$range;
        $end    = Carbon::now();
        $start  = $end->copy()->subMonths($months - 1)->startOfMonth();

        $labels = [];
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $labels[] = $start->copy()->addMonths($i)->format('M Y');
        }

        $raw = DB::table('wallets')
            ->select(DB::raw("DATE_FORMAT(created_at, '%b %Y') as month"), DB::raw('AVG(balance_after) as bal'))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('month')
            ->pluck('bal', 'month')
            ->toArray();

        foreach ($labels as $m) {
            $series[] = isset($raw[$m]) ? round($raw[$m], 2) : 0;
        }

        return collect(array_map(fn($m, $b) => ['month' => $m, 'balance' => $b], $labels, $series));
    }
}
