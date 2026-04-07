<?php

namespace App\Services\Privacy;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Models\DataSubjectRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-4] Data subject request management service.
 *
 * PDPL requires organizations to respond to data subject requests
 * (access, correction, erasure, portability, objection) within 30 days.
 * This service manages the admin-reviewed workflow.
 */
class DataSubjectRequestService
{
    /**
     * Create a new data subject request.
     */
    public function createRequest(
        User $subject,
        DataRequestType $type,
        string $description,
        ?User $requestedBy = null,
        ?array $affectedData = null,
    ): DataSubjectRequest {
        $request = DataSubjectRequest::create([
            'user_id' => $subject->id,
            'requested_by' => $requestedBy?->id ?? $subject->id,
            'request_type' => $type->value,
            'status' => DataRequestStatus::Pending->value,
            'description' => $description,
            'affected_data' => $affectedData,
        ]);

        Log::info('[PDPL] Data subject request created', [
            'request_id' => $request->uuid,
            'user_id' => $subject->id,
            'type' => $type->value,
            'deadline' => $request->deadline_at->toDateString(),
        ]);

        return $request;
    }

    /**
     * Review and update a data subject request.
     */
    public function reviewRequest(
        DataSubjectRequest $request,
        User $reviewer,
        DataRequestStatus $newStatus,
        ?string $adminNotes = null,
    ): DataSubjectRequest {
        $oldStatus = $request->status;

        $request->update([
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'status' => $newStatus->value,
            'admin_notes' => $adminNotes,
            'completed_at' => in_array($newStatus, [DataRequestStatus::Completed, DataRequestStatus::Rejected])
                ? now() : null,
        ]);

        Log::info('[PDPL] Data subject request reviewed', [
            'request_id' => $request->uuid,
            'old_status' => $oldStatus instanceof DataRequestStatus ? $oldStatus->value : $oldStatus,
            'new_status' => $newStatus->value,
            'reviewer_id' => $reviewer->id,
        ]);

        return $request;
    }

    /**
     * Get pending requests that are approaching or past their deadline.
     */
    public function getOverdueRequests(): \Illuminate\Database\Eloquent\Collection
    {
        return DataSubjectRequest::overdue()->get();
    }

    /**
     * Get pending requests count for admin dashboard.
     */
    public function getPendingCount(): int
    {
        return DataSubjectRequest::pending()->count();
    }

    /**
     * Process an erasure request.
     *
     * NOTE: Full erasure is not automated. This marks the request as completed
     * and documents what was done. Actual data removal requires manual steps
     * due to financial record retention requirements.
     */
    public function processErasureRequest(DataSubjectRequest $request, User $reviewer, string $notes): DataSubjectRequest
    {
        if ($request->request_type !== DataRequestType::Erasure) {
            throw new \DomainException('This method only processes erasure requests.');
        }

        // Check for financial record retention constraints
        $user = $request->user;
        $hasActiveOrders = $user->orders()->whereNotIn('general_status', ['completed', 'cancelled'])->exists();
        $hasActiveLoans = $user->checkouts()->whereNotIn('status', ['completed', 'cancelled'])->exists();

        if ($hasActiveOrders || $hasActiveLoans) {
            Log::warning('[PDPL] Erasure blocked: active financial obligations', [
                'request_id' => $request->uuid,
                'user_id' => $user->id,
                'has_active_orders' => $hasActiveOrders,
                'has_active_loans' => $hasActiveLoans,
            ]);

            return $this->reviewRequest(
                $request,
                $reviewer,
                DataRequestStatus::Rejected,
                "Erasure cannot be completed while active financial obligations exist. {$notes}"
            );
        }

        return $this->reviewRequest($request, $reviewer, DataRequestStatus::Completed, $notes);
    }
}
