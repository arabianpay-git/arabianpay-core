<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promise extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'schedule_payment_id',
        'promise_date',
        'method'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function schedulePayment()
    {
        return $this->belongsTo(SchedulePayment::class);
    }
}
