<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayout extends Model
{
    protected $table = 'supplier_payouts';

    protected $fillable = [
        'uuid',
        'supplier_id', // links to the suppliers table
        'order_id', // links to the orders table
        'settlement_id',
        'amount',
        'status',           // e.g., 'pending', 'completed', 'failed'
        'payout_date',
        'notes',
        'created_by', // links to the user table
    ];

    protected $casts = [
        'payout_date' => 'datetime',
        'amount' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Merchant::class, 'supplier_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class, 'settlement_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
