<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'schedule_payment_id',
        'user_id',
        'assigned_to',
        'claim_type',
        'claim_status',
        'priority',
        'notes',
        'contact_method',
        'attempted_at',
        'contacted_at',
        'promised_payment_date',
        'customer_response',
        'customer_reason',
        'promised_amount',
        'next_follow_up',
        'requires_escalation',
        'escalation_reason',
        'attempt_count',
        'communication_log',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'contacted_at' => 'datetime',
        'promised_payment_date' => 'datetime',
        'next_follow_up' => 'datetime',
        'requires_escalation' => 'boolean',
        'communication_log' => 'array',
        'promised_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    // Relationships
    public function schedulePayment()
    {
        return $this->belongsTo(SchedulePayment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('claim_status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('next_follow_up', '<=', now());
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeRequiresEscalation($query)
    {
        return $query->where('requires_escalation', true);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->next_follow_up && $this->next_follow_up->isPast();
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->next_follow_up || !$this->next_follow_up->isPast()) {
            return 0;
        }
        return $this->next_follow_up->diffInDays(now());
    }

    public function getStatusColorAttribute()
    {
        return match($this->claim_status) {
            'pending' => 'warning',
            'attempted' => 'info',
            'contacted' => 'primary',
            'promised' => 'success',
            'failed' => 'danger',
            'resolved' => 'success',
            default => 'secondary'
        };
    }

    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'low' => 'secondary',
            'medium' => 'info',
            'high' => 'warning',
            'urgent' => 'danger',
            default => 'secondary'
        };
    }

    // Helper Methods
    public function addCommunicationLog($type, $details)
    {
        $log = $this->communication_log ?? [];
        $log[] = [
            'type' => $type,
            'timestamp' => now()->toISOString(),
            'details' => $details,
        ];
        $this->update(['communication_log' => $log]);
    }

    public function markAsContacted($response = null, $notes = null)
    {
        $this->update([
            'claim_status' => 'contacted',
            'contacted_at' => now(),
            'customer_response' => $response,
            'notes' => $notes,
        ]);
    }

    public function scheduleFollowUp($date, $notes = null)
    {
        $this->update([
            'next_follow_up' => $date,
            'notes' => $notes,
        ]);
    }

    public function escalate($reason)
    {
        $this->update([
            'requires_escalation' => true,
            'escalation_reason' => $reason,
            'priority' => 'urgent',
        ]);
    }
}