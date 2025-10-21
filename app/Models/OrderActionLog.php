<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderActionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'customer_id',
        'order_id',
        'action_type',
        'description',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Relations
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
