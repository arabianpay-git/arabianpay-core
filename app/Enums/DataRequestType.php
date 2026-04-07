<?php

namespace App\Enums;

/**
 * [PHASE-4] PDPL data subject request types.
 */
enum DataRequestType: string
{
    case Access = 'access';
    case Correction = 'correction';
    case Erasure = 'erasure';
    case Portability = 'portability';
    case Objection = 'objection';
}
