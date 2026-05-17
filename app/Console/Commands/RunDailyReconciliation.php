<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Finance\ReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled command: runs the ledger reconciliation for the previous
 * business day.
 *
 * SAMA MVC §5: Reconciliation output is tamper-evident — the service
 * writes an audit_trails entry keyed by a run UUID. The command itself
 * is idempotent per (business_day, run_id) pair; re-running is safe.
 */
class RunDailyReconciliation extends Command
{
    protected $signature = 'reconciliation:daily {--date= : Business day to reconcile (YYYY-MM-DD). Defaults to yesterday.}';

    protected $description = 'Run the daily ledger reconciliation (SAMA MVC §5 compliance control).';

    public function handle(ReconciliationService $service): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $this->info("Reconciling business day {$date->toDateString()}…");

        try {
            $summary = $service->runDailyReconciliation($date);
        } catch (Throwable $e) {
            Log::error('reconciliation:daily command failed', [
                'exception' => $e,
            ]);
            $this->error('Reconciliation failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Run %s finished with status=%s (variances=%d)',
            $summary['run_id'],
            $summary['status'],
            count($summary['variances'])
        ));

        if ($summary['status'] === 'variance') {
            $this->warn('Variance detected — review the audit trail and escalate to finance.');

            return self::FAILURE;
        }

        return $summary['status'] === 'balanced' ? self::SUCCESS : self::FAILURE;
    }
}
