<?php

namespace App\Services\Finance;

use App\Enums\ApprovalStatus;
use App\Enums\RefundStatus;
use App\Models\ApprovalRequest;
use App\Models\RefundRequest;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\Log;

/**
 * Refund request approval with Maker-Checker enforcement.
 *
 * No refund can be executed without approval from a different user.
 */
class RefundApprovalService
{
    public function __construct(
        private ApprovalService $approvalService,
    ) {}

    /**
     * Submit a refund for approval (maker step).
     */
    public function submitForApproval(
        RefundRequest $refund,
        User $maker,
        ?string $reason = null,
    ): ApprovalRequest {
        return $this->approvalService->submitForApproval(
            entity: $refund,
            actionType: 'approve_refund',
            actor: $maker,
            payload: [
                'refund_amount' => $refund->refund_amount,
                'order_id' => $refund->order_id,
            ],
            reason: $reason ?? $refund->reason,
            beforeState: ['refund_status' => $refund->refund_status],
        );
    }

    /**
     * Approve the refund (checker step).
     */
    public function approve(
        ApprovalRequest $approval,
        User $checker,
        ?string $notes = null,
    ): RefundRequest {
        $this->approvalService->approve($approval, $checker, $notes);

        $refund = $approval->approvable;
        $refund->update([
            'refund_status' => RefundStatus::Approved->value,
            'admin_approval' => 'approved',
        ]);

        $this->approvalService->markExecuted($approval, $checker, [
            'refund_status' => RefundStatus::Approved->value,
        ]);

        Log::info('[REFUND] Approved via maker-checker', [
            'refund_id' => $refund->id,
            'approval_id' => $approval->uuid,
            'approved_by' => $checker->id,
        ]);

        return $refund->fresh();
    }

    /**
     * Reject the refund (checker step).
     */
    public function reject(
        ApprovalRequest $approval,
        User $checker,
        string $reason,
    ): RefundRequest {
        $this->approvalService->reject($approval, $checker, $reason);

        $refund = $approval->approvable;
        $refund->update([
            'refund_status' => RefundStatus::Rejected->value,
            'admin_approval' => 'rejected',
            'reject_reason' => $reason,
        ]);

        Log::info('[REFUND] Rejected via maker-checker', [
            'refund_id' => $refund->id,
            'approval_id' => $approval->uuid,
            'rejected_by' => $checker->id,
            'reason' => $reason,
        ]);

        return $refund->fresh();
    }
}
