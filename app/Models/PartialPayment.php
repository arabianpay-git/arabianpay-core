<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartialPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'schedule_payment_id',
        'partial_amount',
        'partial_due_date',
        'details',
        'status',
        'approval_status',
        'payment_method',
        'paid_at',
        'receipt',
    ];

    protected $casts = [
        'details' => 'array',
        'partial_due_date' => 'date',
        'paid_at' => 'datetime',
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
