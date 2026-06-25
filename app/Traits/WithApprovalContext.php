<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;

trait WithApprovalContext
{
    public static function enterApprovalContext(): void
    {
        Context::add('approval_context_active', true);
    }

    public static function exitApprovalContext(): void
    {
        Context::forget('approval_context_active');
    }

    public static function isInApprovalContext(): bool
    {
        return (bool) Context::get('approval_context_active', false);
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

    /**
     * Register guards that detect direct persistence of financial models
     * outside the approved context.
     */
    protected static function bootWithApprovalContext(): void
    {
        static::creating(function (self $model): void {
            if (! static::isInApprovalContext()) {
                Log::critical('Direct creation on financial model outside approval context', [
                    'model' => static::class,
                    'attributes' => $model->getAttributes(),
                ]);

                try {
                    app(\App\Services\AuditTrailService::class)->logCrudOperation(
                        'unauthorized_direct_creation',
                        class_basename($model),
                        $model->getKey(),
                        'CRITICAL: Financial model created directly — bypassing approval service',
                        null,
                        $model->getAttributes(),
                    );
                } catch (\Throwable) {
                    // AuditTrailService unavailable (e.g., seeding, testing) — Log::critical already fired
                }
            }
        });

        static::updating(function (self $model): void {
            if (! static::isInApprovalContext()) {
                Log::critical('Direct mutation on financial model outside approval context', [
                    'model' => static::class,
                    'id' => $model->getKey(),
                    'dirty' => array_keys($model->getDirty()),
                ]);

                try {
                    app(\App\Services\AuditTrailService::class)->logCrudOperation(
                        'unauthorized_direct_mutation',
                        class_basename($model),
                        $model->getKey(),
                        'CRITICAL: Financial model mutated directly — bypassing approval service',
                        $model->getOriginal(),
                        $model->getDirty(),
                    );
                } catch (\Throwable) {
                    // AuditTrailService unavailable (e.g., seeding, testing) — Log::critical already fired
                }
            }
        });

        static::deleting(function (self $model): void {
            if (! static::isInApprovalContext()) {
                Log::critical('Direct deletion on financial model outside approval context', [
                    'model' => static::class,
                    'id' => $model->getKey(),
                ]);

                try {
                    app(\App\Services\AuditTrailService::class)->logCrudOperation(
                        'unauthorized_direct_deletion',
                        class_basename($model),
                        $model->getKey(),
                        'CRITICAL: Financial model deleted directly — bypassing approval service',
                        $model->toArray(),
                        null,
                    );
                } catch (\Throwable) {
                    // AuditTrailService unavailable (e.g., seeding, testing) — Log::critical already fired
                }
            }
        });
    }
}
