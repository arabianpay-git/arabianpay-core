<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Traits\WithApprovalContext;
use Tests\TestCase;

/**
 * Unit tests for the soft-audit approval context guard.
 *
 * CORE-P1-10 / SAMA CSF 4.1: Financial model mutations must be
 * detectable when they occur outside an approved service context.
 */
class WithApprovalContextTest extends TestCase
{
    /**
     * Use an anonymous class as a concrete host for the trait so the tests
     * remain self-contained and never touch Eloquent or the database.
     */
    private function makeHost(): object
    {
        return new class
        {
            use WithApprovalContext;
        };
    }

    protected function tearDown(): void
    {
        // Always reset static state so no test bleeds into the next.
        $host = $this->makeHost();
        $host::exitApprovalContext();

        parent::tearDown();
    }

    public function test_context_is_inactive_by_default(): void
    {
        $host = $this->makeHost();

        $this->assertFalse(
            $host::isInApprovalContext(),
            'Approval context must be inactive on a fresh host.'
        );
    }

    public function test_enter_sets_context_active(): void
    {
        $host = $this->makeHost();
        $host::enterApprovalContext();

        $this->assertTrue($host::isInApprovalContext());
    }

    public function test_exit_deactivates_context(): void
    {
        $host = $this->makeHost();
        $host::enterApprovalContext();
        $host::exitApprovalContext();

        $this->assertFalse($host::isInApprovalContext());
    }

    public function test_run_in_approval_context_activates_during_callback(): void
    {
        $host = $this->makeHost();
        $capturedInside = null;

        $host::runInApprovalContext(function () use ($host, &$capturedInside): void {
            $capturedInside = $host::isInApprovalContext();
        });

        $this->assertTrue($capturedInside, 'Context must be active inside the callback.');
    }

    public function test_run_in_approval_context_deactivates_after_callback(): void
    {
        $host = $this->makeHost();

        $host::runInApprovalContext(function (): void {
            // no-op
        });

        $this->assertFalse(
            $host::isInApprovalContext(),
            'Context must be inactive after the callback returns.'
        );
    }

    public function test_run_in_approval_context_deactivates_after_exception(): void
    {
        $host = $this->makeHost();

        try {
            $host::runInApprovalContext(function (): void {
                throw new \RuntimeException('boom');
            });
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertFalse(
            $host::isInApprovalContext(),
            'Context must be inactive even when the callback throws.'
        );
    }

    public function test_run_in_approval_context_returns_callback_value(): void
    {
        $host = $this->makeHost();

        $result = $host::runInApprovalContext(fn (): int => 42);

        $this->assertSame(42, $result);
    }

    public function test_context_is_shared_statically_across_instances(): void
    {
        $a = $this->makeHost();
        $b = $this->makeHost();

        $a::enterApprovalContext();

        $this->assertTrue(
            $b::isInApprovalContext(),
            'Static approval context must be visible to all instances of the same class.'
        );
    }
}
