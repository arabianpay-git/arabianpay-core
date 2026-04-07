<?php

namespace App\Enums;

/**
 * [PHASE-2] Supplier payout lifecycle statuses.
 */
enum PayoutStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
