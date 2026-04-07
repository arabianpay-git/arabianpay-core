<?php

namespace App\Enums;

/**
 * [PHASE-2] Settlement lifecycle statuses.
 */
enum SettlementStatus: string
{
    case Draft = 'draft';

    /** Legacy rows; same workflow / permissions as {@see self::PendingApproval}. */
    case Pending = 'pending';

    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
