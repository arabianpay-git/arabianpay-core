<?php

namespace App\Traits;

use App\Models\ApprovalRequest;

/**
 * Trait for models that participate in the Maker-Checker approval workflow.
 */
trait HasApprovalRequests
{
    public function approvalRequests()
    {
        return $this->morphMany(ApprovalRequest::class, 'approvable');
    }

    public function pendingApprovals()
    {
        return $this->approvalRequests()->where('status', 'pending');
    }

    public function latestApproval()
    {
        return $this->morphOne(ApprovalRequest::class, 'approvable')->latestOfMany();
    }
}
