<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sanad extends Model
{
    protected $fillable = [
        'id',
        'order_id',
        'user_id',
        'reference_id',
        'status',
        'total_value',
        'currency',
        'issued_at',
        'approved_at',
        'raw_response',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'approved_at' => 'datetime',
        'raw_response' => 'array',
    ];
}
