<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'bank_code',
        'consent_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
