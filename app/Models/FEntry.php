<?php

namespace App\Models;

use App\Traits\WithApprovalContext;
use Illuminate\Database\Eloquent\Model;

// Financial Entry
class FEntry extends Model
{
    use WithApprovalContext;

    protected $fillable = [
        'transaction_id', // links to f_transactions table
        'reference_id', // optional, links to another order or payment
        'user_id', // required, links to a users table
        'customer_id', // nullable, if the entry is linked to a customer
        'supplier_id', // nullable, if the entry is linked to a supplier
        'order_id', // nullable, if the entry is linked to an order
        'payment_id', // nullable, if the entry is linked to a payments table
        'account_id', // required, links to an accounts table
        'account_name',
        'debit',      // amount debited
        'credit',     // amount credited
        'status',     // e.g., 'pending', 'completed', 'failed'
        'entry_date',
        'notes',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'entry_date' => 'datetime',
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
        return $this->belongsTo(FAccounts::class, 'account_id', 'id');
    }

    public function transaction()
    {
        return $this->belongsTo(FTransaction::class, 'transaction_id');
    }
}
