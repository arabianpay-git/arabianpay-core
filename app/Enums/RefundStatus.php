<?php

namespace App\Enums;

/**
 * [PHASE-2] Refund request statuses.
 */
enum RefundStatus: string
{
    case Pending = 'pending';
    case SellerApproved = 'seller_approved';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Processed = 'processed';
    case Cancelled = 'cancelled';
}
