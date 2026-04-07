<?php

namespace App\Services\Finance;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\CustomerCreditLimit;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Credit limit changes with Maker-Checker approval.
 *
 * Proposed changes are stored in the approval request payload and only applied
 * after a different user approves. The original value is preserved until approval.
 */
class CreditLimitApprovalService
{
    public function __construct(
        private ApprovalService $approvalService,
    ) {}

    /**
     * Propose a credit limit change (maker step).
     */
    public function proposeLimitChange(
        CustomerCreditLimit $creditLimit,
        array $proposedValues,
        User $maker,
        string $reason,
    ): ApprovalRequest {
        $beforeState = [
            'limit_arabianpay_before' => $creditLimit->limit_arabianpay_before,
            'limit_arabianpay_after' => $creditLimit->limit_arabianpay_after,
            'comission' => $creditLimit->comission,
        ];

        return $this->approvalService->submitForApproval(
            entity: $creditLimit,
            actionType: 'update',
            actor: $maker,
            payload: $proposedValues,
            reason: $reason,
            beforeState: $beforeState,
        );
    }

    /**
     * Approve and apply a credit limit change (checker step).
     */
    public function approveAndApply(
        ApprovalRequest $approval,
        User $checker,
        ?string $notes = null,
    ): CustomerCreditLimit {
        // The generic service handles self-approval blocking
        $this->approvalService->approve($approval, $checker, $notes);

        // Apply the change
        $creditLimit = $approval->approvable;
        $payload = $approval->payload;

        DB::beginTransaction();
        try {
            $creditLimit->update($payload);

            $this->approvalService->markExecuted($approval, $checker, [
                'limit_arabianpay_before' => $creditLimit->limit_arabianpay_before,
                'limit_arabianpay_after' => $creditLimit->limit_arabianpay_after,
                'comission' => $creditLimit->comission,
            ]);

            DB::commit();

            Log::info('[CREDIT-LIMIT] Change approved and applied', [
                'approval_id' => $approval->uuid,
                'credit_limit_id' => $creditLimit->id,
                'user_id' => $creditLimit->user_id,
                'approved_by' => $checker->id,
            ]);

            return $creditLimit->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject a credit limit change — original values preserved.
     */
    public function reject(ApprovalRequest $approval, User $checker, string $reason): ApprovalRequest
    {
        return $this->approvalService->reject($approval, $checker, $reason);
    }
}
