<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\RefundRequest;
use App\Models\User;
use App\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;

class RefundApprovalService
{
    public function __construct(private AuditTrailService $auditTrail) {}

    /**
     * Approve a refund request, recording a full audit trail.
     */
    public function approve(RefundRequest $refund, User $actor): void
    {
        DB::transaction(function () use ($refund, $actor): void {
            $before = $refund->toArray();

            $refund->update(['refund_status' => 'approved']);

            $this->auditTrail->logUpdated(
                $refund->fresh(),
                $before,
                'Refund request approved by '.$actor->email,
            );
        });
    }

    /**
     * Reject a refund request, recording a full audit trail.
     */
    public function reject(RefundRequest $refund, User $actor, string $reason = ''): void
    {
        DB::transaction(function () use ($refund, $actor, $reason): void {
            $before = $refund->toArray();

            $refund->update(['refund_status' => 'rejected']);

            $summary = 'Refund request rejected by '.$actor->email;
            if ($reason !== '') {
                $summary .= '. Reason: '.$reason;
            }

            $this->auditTrail->logUpdated(
                $refund->fresh(),
                $before,
                $summary,
            );
        });
    }
}
