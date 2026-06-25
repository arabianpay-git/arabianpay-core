<?php

namespace Tests\Feature\Console;

use App\Services\Finance\ReconciliationService;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RunDailyReconciliationTest extends TestCase
{
    public function test_runs_successfully_when_balanced(): void
    {
        $this->mock(ReconciliationService::class)
            ->shouldReceive('runDailyReconciliation')
            ->once()
            ->withArgs(fn ($date) => true)
            ->andReturn([
                'run_id' => '550e8400-e29b-41d4-a716-446655440000',
                'business_day' => now()->subDay()->toDateString(),
                'status' => 'balanced',
                'variances' => [],
                'started_at' => now()->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'ledger' => ['balanced' => true],
                'orders' => ['delivered_count' => 0],
                'payments' => ['payment_count' => 0],
            ]);

        $exitCode = Artisan::call('reconciliation:daily');

        $this->assertSame(0, $exitCode);
    }

    public function test_returns_failure_when_variance_detected(): void
    {
        $this->mock(ReconciliationService::class)
            ->shouldReceive('runDailyReconciliation')
            ->once()
            ->andReturn([
                'run_id' => '660e8400-e29b-41d4-a716-446655440001',
                'business_day' => now()->subDay()->toDateString(),
                'status' => 'variance',
                'variances' => [
                    [
                        'type' => 'ledger_imbalance',
                        'debit_minor' => 50000,
                        'credit_minor' => 49900,
                        'delta_minor' => 100,
                    ],
                ],
                'started_at' => now()->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'ledger' => ['balanced' => false],
                'orders' => ['delivered_count' => 0],
                'payments' => ['payment_count' => 0],
            ]);

        $exitCode = Artisan::call('reconciliation:daily');

        $this->assertSame(1, $exitCode);
    }

    public function test_accepts_custom_date_option(): void
    {
        $this->mock(ReconciliationService::class)
            ->shouldReceive('runDailyReconciliation')
            ->once()
            ->withArgs(fn ($date) => $date->toDateString() === '2025-01-15')
            ->andReturn([
                'run_id' => '770e8400-e29b-41d4-a716-446655440002',
                'business_day' => '2025-01-15',
                'status' => 'balanced',
                'variances' => [],
                'started_at' => now()->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'ledger' => ['balanced' => true],
                'orders' => ['delivered_count' => 0],
                'payments' => ['payment_count' => 0],
            ]);

        $exitCode = Artisan::call('reconciliation:daily', ['--date' => '2025-01-15']);

        $this->assertSame(0, $exitCode);
    }

    public function test_handles_exception_from_service(): void
    {
        $this->mock(ReconciliationService::class)
            ->shouldReceive('runDailyReconciliation')
            ->once()
            ->andThrow(new \RuntimeException('Database connection lost'));

        $exitCode = Artisan::call('reconciliation:daily');

        $this->assertSame(1, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Database connection lost', $output);
    }
}
