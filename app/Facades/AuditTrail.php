<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AuditTrail extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'audit-trail';
    }
}
