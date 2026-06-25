<?php

namespace App\Models;

use App\Traits\WithApprovalContext;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvestmentPool extends Model
{
    use HasFactory, WithApprovalContext;

    protected $fillable = [
        'name',
        'uuid',
        'start_date',
        'end_date',
        'expected_collections',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_disbursed' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'expected_collections' => 'decimal:2',
        'collection_rate' => 'decimal:2',
    ];

    // Auto-generate UUID when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pool) {
            $pool->uuid = (string) Str::uuid();
        });
    }

    // Relationships
    public function checkouts()
    {
        return $this->hasMany(Checkout::class, 'pool_id');
    }

    // InvestmentPool->checkouts->schedulePayments->payments
    public function payments()
    {
        return $this->hasManyThrough(Payment::class, Checkout::class, 'pool_id', 'checkout_id');
    }

    public function schedulePayments()
    {
        return $this->hasManyThrough(SchedulePayment::class, Checkout::class, 'pool_id', 'checkout_id');
    }

    // Calculated Properties
    public function getTotalAmountAttribute()
    {
        return $this->checkouts()->sum('total_amount') ?? 0;
    }

    public function getActiveCheckoutsAttribute()
    {
        return $this->checkouts()->where('status', 'active')->count();
    }

    public function getCompletedPaymentsAttribute()
    {
        return $this->payments()->where('payment_status', 'completed')->sum('amount') ?? 0;
    }

    public function getPendingPaymentsAttribute()
    {
        return $this->schedulePayments()->where('status', 'pending')->sum('amount') ?? 0;
    }

    public function getDefaultedPaymentsAttribute()
    {
        return $this->schedulePayments()->where('status', 'defaulted')->sum('amount') ?? 0;
    }

    public function getCurrentCollectionRateAttribute()
    {
        if ($this->expected_collections == 0) {
            return 0;
        }

        return ($this->total_collected / $this->expected_collections) * 100;
    }

    public function getDaysRemainingAttribute()
    {
        return $this->end_date->diffInDays(Carbon::now(), false);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'active' && Carbon::now()->between($this->start_date, $this->end_date);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrent($query)
    {
        $now = Carbon::now();

        return $query->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeByMonth($query, $year, $month)
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();

        return $query->whereYear('start_date', $year)
            ->whereMonth('start_date', $month);
    }

    // Helper Methods
    public static function createMonthlyPool($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->addDays(60); // 60-day lifecycle

        return self::create([
            'name' => $startDate->format('F Y').' Pool',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => "Investment pool for checkouts created in {$startDate->format('F Y')}",
        ]);
    }

    public function updateMetrics()
    {
        $this->forceFill([
            'total_checkouts' => $this->checkouts()->count(),
            'total_disbursed' => $this->checkouts()->sum('total_amount') ?? 0,
            'total_collected' => $this->payments()->where('payment_status', 'paid')->sum('amount') ?? 0,
            'expected_collections' => $this->schedulePayments()->sum('instalment_amount') ?? 0,
            'collection_rate' => $this->getCurrentCollectionRateAttribute(),
        ])->save();
    }

    public function closePool()
    {
        $this->updateMetrics();
        $this->update(['status' => 'closed']);
    }
}
