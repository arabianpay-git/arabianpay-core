<?php

namespace App\Enums;

/**
 * [PHASE-2] Order general/workflow statuses.
 */
enum OrderGeneralStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
