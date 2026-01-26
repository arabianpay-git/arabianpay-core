<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Settlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'settlement_number',
        'supplier_user_id',
        'start_date',
        'end_date',
        'settlement_date',
        'total_amount',
        'commission_amount',
        'payable_amount',
        'status',
        'paid_at',
        'paid_by',
        'approved_at',
        'approved_by',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'settlement_date' => 'date',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'payable_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'settlement_id');
    }

    public function payouts()
    {
        return $this->hasMany(SupplierPayout::class, 'settlement_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
