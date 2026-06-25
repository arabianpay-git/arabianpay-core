<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulePaymentReminder extends Model
{
    protected $fillable = [
        'schedule_payment_id',
        'type',
        'target_date',
        'sent_at',
    ];
}
