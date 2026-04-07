<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// Financial Entry
class FEntry extends Model
{
    protected $fillable = [
        'transaction_id',
        'reference_id',
        'user_id',
        'customer_id',
        'supplier_id',
        'order_id',
        'payment_id',
        'account_id',
        'account_name',
        'debit',
        'credit',
        'status',
        'entry_date',
        'notes',
    ];

    // [PHASE-2] Added decimal casts for financial amounts
    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
    public function account()
    {
        return $this->belongsTo(FAccounts::class, 'account_id','id');
    }
    public function transaction()
    {
        return $this->belongsTo(FTransaction::class, 'transaction_id');
    }
}
