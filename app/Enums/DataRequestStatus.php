<?php

namespace App\Enums;

/**
 * [PHASE-4] Data subject request lifecycle statuses.
 */
enum DataRequestStatus: string
{
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
