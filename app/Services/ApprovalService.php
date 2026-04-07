<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Generic Maker-Checker approval service.
 *
 * Enforces:
 * - Requester cannot approve their own request
 * - Status transitions are validated
 * - Duplicate pending requests for the same entity+action are blocked
 * - All actions are audit-logged
 */
class ApprovalService
{
    /**
     * Submit an entity for approval.
     *
     * @param Model  $entity      The model being submitted (Settlement, CreditLimit, etc.)
     * @param string $actionType  e.g. 'create', 'update', 'execute', 'override'
     * @param User   $actor       The user submitting
     * @param array  $payload     Proposed changes or action parameters
     * @param string|null $reason Justification
     * @param array|null $beforeState  State before proposed change
     * @return ApprovalRequest
     */
    public function submitForApproval(
        Model $entity,
        string $actionType,
        User $actor,
        array $payload = [],
        ?string $reason = null,
        ?array $beforeState = null,
    ): ApprovalRequest {
        // Block duplicate pending requests for same entity + action
        $existing = ApprovalRequest::where('approvable_type', get_class($entity))
            ->where('approvable_id', $entity->getKey())
            ->where('action_type', $actionType)
            ->where('status', ApprovalStatus::Pending)
            ->first();

        if ($existing) {
            Log::warning('[APPROVAL] Duplicate submission blocked', [
                'entity' => get_class($entity),
                'entity_id' => $entity->getKey(),
                'action' => $actionType,
                'existing_id' => $existing->id,
            ]);
            throw new \DomainException(
                "A pending approval request already exists for this action (#{$existing->uuid})."
            );
        }

        $approval = ApprovalRequest::create([
            'approvable_type' => get_class($entity),
            'approvable_id' => $entity->getKey(),
            'action_type' => $actionType,
            'status' => ApprovalStatus::Pending->value,
            'requested_by' => $actor->id,
            'payload' => $payload,
            'request_reason' => $reason,
            'before_state' => $beforeState,
        ]);

        Log::info('[APPROVAL] Submitted for approval', [
            'approval_id' => $approval->uuid,
            'entity' => get_class($entity),
            'entity_id' => $entity->getKey(),
            'action' => $actionType,
            'requested_by' => $actor->id,
        ]);

        return $approval;
    }

    /**
     * Approve a pending request.
     *
     * @throws \DomainException if self-approval or invalid status
     */
    public function approve(ApprovalRequest $approval, User $actor, ?string $notes = null): ApprovalRequest
    {
        $this->validateTransition($approval, ApprovalStatus::Approved);

        // Maker-checker: requester cannot approve their own request
        if ($approval->requested_by === $actor->id) {
            Log::warning('[APPROVAL] Self-approval blocked', [
                'approval_id' => $approval->uuid,
                'user_id' => $actor->id,
            ]);
            throw new \DomainException(
                'Maker-checker violation: you cannot approve your own request.'
            );
        }

        $approval->update([
            'status' => ApprovalStatus::Approved->value,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        Log::info('[APPROVAL] Approved', [
            'approval_id' => $approval->uuid,
            'entity' => $approval->approvable_type,
            'entity_id' => $approval->approvable_id,
            'approved_by' => $actor->id,
            'requested_by' => $approval->requested_by,
        ]);

        return $approval->fresh();
    }

    /**
     * Reject a pending request.
     *
     * @throws \DomainException if self-rejection or invalid status
     */
    public function reject(ApprovalRequest $approval, User $actor, string $reason): ApprovalRequest
    {
        $this->validateTransition($approval, ApprovalStatus::Rejected);

        if ($approval->requested_by === $actor->id) {
            // Makers CAN cancel their own request, but cannot "reject" — use cancel instead
            throw new \DomainException(
                'Use cancel instead of reject for your own requests.'
            );
        }

        $approval->update([
            'status' => ApprovalStatus::Rejected->value,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        Log::info('[APPROVAL] Rejected', [
            'approval_id' => $approval->uuid,
            'entity' => $approval->approvable_type,
            'entity_id' => $approval->approvable_id,
            'rejected_by' => $actor->id,
            'reason' => $reason,
        ]);

        return $approval->fresh();
    }

    /**
     * Mark an approved request as executed.
     *
     * @throws \DomainException if not approved or same user as approver
     */
    public function markExecuted(
        ApprovalRequest $approval,
        User $actor,
        ?array $afterState = null,
    ): ApprovalRequest {
        if ($approval->status !== ApprovalStatus::Approved) {
            throw new \DomainException('Only approved requests can be executed.');
        }

        $approval->update([
            'status' => ApprovalStatus::Executed->value,
            'executed_by' => $actor->id,
            'executed_at' => now(),
            'after_state' => $afterState,
        ]);

        Log::info('[APPROVAL] Executed', [
            'approval_id' => $approval->uuid,
            'entity' => $approval->approvable_type,
            'entity_id' => $approval->approvable_id,
            'executed_by' => $actor->id,
        ]);

        return $approval->fresh();
    }

    /**
     * Cancel a pending request (by the requester).
     */
    public function cancel(ApprovalRequest $approval, User $actor): ApprovalRequest
    {
        $this->validateTransition($approval, ApprovalStatus::Cancelled);

        $approval->update([
            'status' => ApprovalStatus::Cancelled->value,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);

        Log::info('[APPROVAL] Cancelled', [
            'approval_id' => $approval->uuid,
            'cancelled_by' => $actor->id,
        ]);

        return $approval->fresh();
    }

    /**
     * Check if an entity has a pending approval for a given action.
     */
    public function hasPendingApproval(Model $entity, string $actionType): bool
    {
        return ApprovalRequest::where('approvable_type', get_class($entity))
            ->where('approvable_id', $entity->getKey())
            ->where('action_type', $actionType)
            ->where('status', ApprovalStatus::Pending)
            ->exists();
    }

    /**
     * Get the latest approval for an entity + action.
     */
    public function getLatestApproval(Model $entity, string $actionType): ?ApprovalRequest
    {
        return ApprovalRequest::where('approvable_type', get_class($entity))
            ->where('approvable_id', $entity->getKey())
            ->where('action_type', $actionType)
            ->latest()
            ->first();
    }

    /**
     * Validate that the status transition is allowed.
     */
    private function validateTransition(ApprovalRequest $approval, ApprovalStatus $target): void
    {
        $allowed = match ($approval->status) {
            ApprovalStatus::Pending => [ApprovalStatus::Approved, ApprovalStatus::Rejected, ApprovalStatus::Cancelled],
            ApprovalStatus::Approved => [ApprovalStatus::Executed, ApprovalStatus::Cancelled],
            default => [],
        };

        if (! in_array($target, $allowed)) {
            throw new \DomainException(
                "Cannot transition from '{$approval->status->value}' to '{$target->value}'."
            );
        }
    }
}
