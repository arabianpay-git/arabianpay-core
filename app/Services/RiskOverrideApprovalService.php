<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Risk score override with Maker-Checker approval.
 *
 * No risk score override takes effect until approved by a different user.
 * Before/after values are always captured for audit.
 */
class RiskOverrideApprovalService
{
    public function __construct(
        private ApprovalService $approvalService,
    ) {}

    /**
     * Request a risk score override (maker step).
     *
     * @param Model  $entity     The risk-scored entity (User, Merchant, etc.)
     * @param string $field      Which score field is being overridden
     * @param mixed  $oldValue   Current value
     * @param mixed  $newValue   Proposed value
     * @param User   $maker      Who is requesting
     * @param string $reason     Why the override is needed
     */
    public function requestOverride(
        Model $entity,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        User $maker,
        string $reason,
    ): ApprovalRequest {
        return $this->approvalService->submitForApproval(
            entity: $entity,
            actionType: 'risk_override',
            actor: $maker,
            payload: [
                'field' => $field,
                'new_value' => $newValue,
            ],
            reason: $reason,
            beforeState: [
                'field' => $field,
                'old_value' => $oldValue,
            ],
        );
    }

    /**
     * Approve and apply the risk override (checker step).
     */
    public function approveAndApply(
        ApprovalRequest $approval,
        User $checker,
        ?string $notes = null,
    ): Model {
        $this->approvalService->approve($approval, $checker, $notes);

        $entity = $approval->approvable;
        $field = $approval->payload['field'];
        $newValue = $approval->payload['new_value'];

        $entity->{$field} = $newValue;
        $entity->save();

        $this->approvalService->markExecuted($approval, $checker, [
            'field' => $field,
            'applied_value' => $newValue,
        ]);

        Log::info('[RISK-OVERRIDE] Override approved and applied', [
            'approval_id' => $approval->uuid,
            'entity' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'field' => $field,
            'old_value' => $approval->before_state['old_value'] ?? null,
            'new_value' => $newValue,
            'approved_by' => $checker->id,
        ]);

        return $entity->fresh();
    }

    /**
     * Reject the override — no changes applied.
     */
    public function reject(ApprovalRequest $approval, User $checker, string $reason): ApprovalRequest
    {
        return $this->approvalService->reject($approval, $checker, $reason);
    }
}
