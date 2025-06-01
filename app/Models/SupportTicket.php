<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;
    use LogsModelActions;

        protected static $logAttributes = ['status', 'amount', 'due_date'];
        protected static $logOnlyDirty = true; // Save only changed attributes
        protected static $logName = 'support_ticket'; // Custom log name
    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'details',
        'files',
        'reply',
        'status'
    ];

    protected $casts = [
        'files' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
