<?php

namespace App\Models;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * [PHASE-4] PDPL data subject request model.
 *
 * Tracks access, correction, erasure, portability, and objection requests
 * from data subjects. Admin-reviewed workflow.
 */
class DataSubjectRequest extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'requested_by',
        'reviewed_by',
        'request_type',
        'status',
        'description',
        'admin_notes',
        'affected_data',
        'reviewed_at',
        'completed_at',
        'deadline_at',
    ];

    protected $casts = [
        'request_type' => DataRequestType::class,
        'status' => DataRequestStatus::class,
        'affected_data' => 'array',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?: (string) Str::uuid();
            // PDPL requires response within 30 days
            $model->deadline_at = $model->deadline_at ?: now()->addDays(30);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', DataRequestStatus::Pending);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
            ->whereNotIn('status', [
                DataRequestStatus::Completed->value,
                DataRequestStatus::Rejected->value,
                DataRequestStatus::Cancelled->value,
            ]);
    }
}
