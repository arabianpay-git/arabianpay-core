<?php

namespace App\Policies;

use App\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ApprovalRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('approval.view');
    }

    public function view(User $user, ApprovalRequest $approval): bool
    {
        if ($user->can('approval.manage')) {
            return true;
        }

        // Requesters can always see their own
        return $approval->requested_by === $user->id;
    }

    public function approve(User $user, ApprovalRequest $approval): Response
    {
        if (! $user->can('approval.manage')) {
            return Response::deny('You do not have permission to approve requests.');
        }

        if ($approval->requested_by === $user->id) {
            return Response::deny('Maker-checker: you cannot approve your own request.');
        }

        return Response::allow();
    }

    public function reject(User $user, ApprovalRequest $approval): Response
    {
        if (! $user->can('approval.manage')) {
            return Response::deny('You do not have permission to reject requests.');
        }

        if ($approval->requested_by === $user->id) {
            return Response::deny('Use cancel to withdraw your own request.');
        }

        return Response::allow();
    }
}
