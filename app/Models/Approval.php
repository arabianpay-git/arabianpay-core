<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'user_id',
        'employee_id',
        'commission',
        'reason',
        'contract',
        'fahman_score',
        'payment_schedule',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
