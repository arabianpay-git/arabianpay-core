<?php

namespace App\Models;

use App\Traits\WithApprovalContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

// Financial Transaction
class FTransaction extends Model
{
    protected $table = 'f_transactions';

    protected $fillable = [
        'uuid',
        'checkout_id', // links to the checkouts table
        'customer_id', // nullable, if the transaction is linked to a customer
        'supplier_id', // nullable, if the transaction is linked to a supplier
        'user_id', // required, links to a users table
        'order_id', // nullable, if the transaction is linked to an order
        'payment_id', // nullable, if the transaction is linked to a payments table
        'transaction_type', // e.g., 'payment', 'refund', 'withdrawal'
        'amount',
        'status',           // e.g., 'pending', 'completed', 'failed'
        'transaction_date',
        'notes',
    ];

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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class, 'checkout_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Merchant::class, 'supplier_id');
    }

    public function entries()
    {
        return $this->hasMany(FEntry::class, 'transaction_id');
    }
}
