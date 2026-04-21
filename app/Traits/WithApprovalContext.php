<?php

declare(strict_types=1);

namespace App\Traits;

trait WithApprovalContext
{
    private static bool $approvalContextActive = false;

    public static function enterApprovalContext(): void
    {
        static::$approvalContextActive = true;
    }

    public static function exitApprovalContext(): void
    {
        static::$approvalContextActive = false;
    }

    public static function isInApprovalContext(): bool
    {
        return static::$approvalContextActive;
    }

    /**
     * Run a callable inside the approval context.
     */
    public static function runInApprovalContext(callable $callback): mixed
    {
        static::enterApprovalContext();
        try {
            return $callback();
        } finally {
            static::exitApprovalContext();
        }
    }
}
