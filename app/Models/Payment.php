<?php

namespace App\Models;

use App\Traits\WithApprovalContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_payment_id',
        'checkout_id',
        'seller_id',
        'order_id',
        'amount',
        'payment_details',
        'invoice_number',
        'txn_code',
        'tax_number',
        'payment_status',
    ];

    // Generate a UUID when creating a new payment
    public static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            $payment->uuid = (string) Str::uuid();
        });
    }

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            if (! WithApprovalContext::isInApprovalContext()) {
                Log::critical('Direct mutation on financial model outside approval context', [
                    'model' => static::class,
                    'id' => $model->getKey(),
                    'dirty' => array_keys($model->getDirty()),
                ]);

                try {
                    app(\App\Services\AuditTrailService::class)->logCrudOperation(
                        'unauthorized_direct_mutation',
                        class_basename($model),
                        $model->getKey(),
                        'CRITICAL: Financial model mutated directly — bypassing approval service',
                        $model->getOriginal(),
                        $model->getDirty(),
                    );
                } catch (\Throwable) {
                    // AuditTrailService unavailable (e.g., seeding, testing) — Log::critical already fired
                }
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // get customer using user_id
    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id', 'user_id');
    }

    public function schedulePayment()
    {
        return $this->belongsTo(SchedulePayment::class, 'schedule_payment_id');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class, 'checkout_id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
