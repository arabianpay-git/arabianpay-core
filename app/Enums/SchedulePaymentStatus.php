<?php

namespace App\Enums;

/**
 * [PHASE-2] Schedule payment lifecycle statuses.
 */
enum SchedulePaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Due = 'due';
    case Current = 'current';
    case Paid = 'paid';
    case Late = 'late';
    case Failed = 'failed';
    case PartiallyPaid = 'partially_paid';

    case Cancelled = 'cancelled';

    /** US spelling; legacy rows — same meaning as {@see self::Cancelled}. */
    case Canceled = 'canceled';
}
