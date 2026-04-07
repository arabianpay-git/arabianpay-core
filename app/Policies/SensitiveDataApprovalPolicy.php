<?php

namespace App\Policies;

use App\Models\SensitiveDataApproval;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SensitiveDataApprovalPolicy
{
    /**
     * Determine whether the user can view any sensitive data approvals.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('sensitive-data.access');
    }

    /**
     * Determine whether the user can view the sensitive data approval.
     *
     * Users can always see their own requests.
     */
    public function view(User $user, SensitiveDataApproval $approval): bool
    {
        if ($approval->requested_by === $user->id) {
            return true;
        }

        return $user->can('sensitive-data.access');
    }

    /**
     * Determine whether the user can create a sensitive data approval request.
     *
     * Any admin or employee with access can request.
     */
    public function create(User $user): bool
    {
        return $user->can('sensitive-data.access');
    }

    /**
     * Determine whether the user can approve the sensitive data approval request.
     *
     * Maker-checker: the approver must not be the requester.
     */
    public function approve(User $user, SensitiveDataApproval $approval): Response
    {
        if ($approval->requested_by === $user->id) {
            return Response::deny('You cannot approve your own request.');
        }

        return $user->can('sensitive-data.approve')
            ? Response::allow()
            : Response::deny('You do not have permission to approve sensitive data requests.');
    }
}
