<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = ['phone', 'code', 'expires_at', 'sends'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    // scope: only active (not expired, not used)
    public function scopeActive($query)
    {
        return $query->where('used', false)
            ->where('expires_at', '>', Carbon::now());
    }
}
