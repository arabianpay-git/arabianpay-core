<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    //get customer using user_id
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
