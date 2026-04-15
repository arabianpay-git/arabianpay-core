<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\AuditTrail;
use App\Models\FEntry;
use App\Models\Order;
use App\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Daily financial reconciliation.
 *
 * SAMA MVC §5 / CSF 4.2: Every BNPL originator must reconcile internal
 * ledgers against source-of-truth transaction records daily, detect
 * variances, and retain tamper-evident evidence of each reconciliation
 * run for a minimum of 10 years.
 *
 * This first-pass implementation covers the ledger-internal invariant
 * (debits == credits per day) and records each run in the audit_trails
 * table. Future enhancements (tracked in CORE-P1/P2) should extend this
 * service with: cross-system reconciliation against payments gateway
 * settlements, SIMAH/Nafith submissions, and bank statement ingestion.
 */
class ReconciliationService
{
    /**
     * Minor units per major unit for all SAR amounts. All sums in this
     * service are computed in minor units (halalas) to avoid floating
     * point drift. The invariant tolerance is 1 halala (0.01 SAR).
     */
    private const MINOR_UNITS = 100;

    private const TOLERANCE_MINOR_UNITS = 1;

    /**
     * Run the daily reconciliation for the given business day.
     *
     * @param  Carbon|null  $date  Business day to reconcile; defaults to
     *                             yesterday so the 02:30 cron covers the
     *                             closed day.
     * @return array<string,mixed> Structured result used by tests, the
     *                             scheduled command, and the audit trail.
     */
    public function runDailyReconciliation(?Carbon $date = null): array
    {
        $businessDay = CarbonImmutable::instance($date ?? Carbon::yesterday());
        $startedAt = CarbonImmutable::now();
        $runId = (string) Str::uuid();

        $summary = [
            'run_id' => $runId,
            'business_day' => $businessDay->toDateString(),
            'started_at' => $startedAt->toIso8601String(),
            'completed_at' => null,
            'status' => 'running',
            'ledger' => null,
            'orders' => null,
            'payments' => null,
            'variances' => [],
            'error' => null,
        ];

        try {
            $summary['ledger'] = $this->checkLedgerInvariant($businessDay);
            $summary['orders'] = $this->summariseOrders($businessDay);
            $summary['payments'] = $this->summarisePayments($businessDay);

            if (! $summary['ledger']['balanced']) {
                $summary['variances'][] = [
                    'type' => 'ledger_imbalance',
                    'debit_minor' => $summary['ledger']['total_debit_minor'],
                    'credit_minor' => $summary['ledger']['total_credit_minor'],
                    'delta_minor' => $summary['ledger']['delta_minor'],
                ];
            }

            $summary['status'] = empty($summary['variances']) ? 'balanced' : 'variance';
        } catch (Throwable $e) {
            $summary['status'] = 'failed';
            $summary['error'] = $e->getMessage();
            Log::error('Daily reconciliation failed', [
                'run_id' => $runId,
                'business_day' => $businessDay->toDateString(),
                'exception' => $e,
            ]);
        }

        $summary['completed_at'] = CarbonImmutable::now()->toIso8601String();

        $this->recordAuditTrail($summary);

        return $summary;
    }

    /**
     * Verify ledger invariant: sum(debit) == sum(credit) for f_entries
     * posted within the business day.
     *
     * @return array<string,mixed>
     */
    private function checkLedgerInvariant(CarbonImmutable $day): array
    {
        $start = $day->startOfDay();
        $end = $day->endOfDay();

        $entries = FEntry::query()
            ->whereBetween('entry_date', [$start, $end])
            ->get(['debit', 'credit']);

        $totalDebitMinor = $entries->sum(fn (FEntry $e) => $this->toMinor($e->debit));
        $totalCreditMinor = $entries->sum(fn (FEntry $e) => $this->toMinor($e->credit));
        $deltaMinor = $totalDebitMinor - $totalCreditMinor;

        return [
            'entry_count' => $entries->count(),
            'total_debit_minor' => $totalDebitMinor,
            'total_credit_minor' => $totalCreditMinor,
            'delta_minor' => $deltaMinor,
            'balanced' => abs($deltaMinor) <= self::TOLERANCE_MINOR_UNITS,
        ];
    }

    /**
     * Summarise orders delivered on the business day. Kept as an
     * informational counter; variance detection against settlements
     * will be added in CORE-P1.
     *
     * @return array<string,mixed>
     */
    private function summariseOrders(CarbonImmutable $day): array
    {
        $start = $day->startOfDay();
        $end = $day->endOfDay();

        $orders = Order::query()
            ->whereBetween('delivered_at', [$start, $end])
            ->get(['grand_total', 'commission_amount', 'delivery_status']);

        return [
            'delivered_count' => $orders->count(),
            'gross_total_minor' => $orders->sum(fn (Order $o) => $this->toMinor($o->grand_total ?? 0)),
            'commission_total_minor' => $orders->sum(fn (Order $o) => $this->toMinor($o->commission_amount ?? 0)),
        ];
    }

    /**
     * Summarise payments captured on the business day.
     *
     * @return array<string,mixed>
     */
    private function summarisePayments(CarbonImmutable $day): array
    {
        $start = $day->startOfDay();
        $end = $day->endOfDay();

        $payments = Payment::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['amount', 'payment_status']);

        return [
            'payment_count' => $payments->count(),
            'total_amount_minor' => $payments->sum(fn (Payment $p) => $this->toMinor($p->amount ?? 0)),
            'by_status' => $payments
                ->groupBy('payment_status')
                ->map(fn (Collection $group) => $group->count())
                ->toArray(),
        ];
    }

    private function toMinor(float|int|string|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }

        return (int) round(((float) $amount) * self::MINOR_UNITS);
    }

    /**
     * Persist an audit trail row for the reconciliation run.
     *
     * We write directly (bypassing the request-scoped AuditTrailService)
     * because daily reconciliation runs inside a scheduled command with
     * no HTTP request context.
     */
    private function recordAuditTrail(array $summary): void
    {
        try {
            AuditTrail::create([
                'timestamp' => now(),
                'environment' => config('app.env'),
                'request_id' => $summary['run_id'],
                'correlation_id' => $summary['run_id'],
                'actor_type' => 'system',
                'actor_id' => 'scheduler',
                'actor_email' => 'scheduler@arabianpay.internal',
                'actor_role' => 'system',
                'event_category' => 'finance',
                'event_type' => 'daily_reconciliation',
                'entity_type' => 'reconciliation_run',
                'entity_id' => $summary['run_id'],
                'action_summary' => sprintf(
                    'Daily reconciliation for %s: %s',
                    $summary['business_day'],
                    $summary['status']
                ),
                'properties' => json_encode($summary),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to persist reconciliation audit trail', [
                'run_id' => $summary['run_id'],
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
