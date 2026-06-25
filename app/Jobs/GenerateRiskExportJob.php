<?php

namespace App\Jobs;

use App\Services\RiskAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateRiskExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $exportId;

    public array $filters;

    public int $tries = 3;

    protected RiskAnalyticsService $riskAnalyticsService;

    /**
     * Create a new job instance.
     */
    public function __construct(string $exportId, array $filters = [])
    {
        $this->exportId = $exportId;
        $this->filters = $filters;
        $this->riskAnalyticsService = app(RiskAnalyticsService::class);
        $this->queue = 'default';
        $this->tries = 3;
    }

    public function handle()
    {
        // ensure exports directory exists
        $exportDir = storage_path('app/exports');
        if (! is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        $filename = "risk_scores_{$this->exportId}.csv";
        $filePath = $exportDir.DIRECTORY_SEPARATOR.$filename;

        // cache key used by controller/status
        $cacheKey = 'risk_export_path_'.$this->exportId;

        try {
            // Build base query (same filters as before)
            $typeParam = strtolower($this->filters['type'] ?? '');
            $allowedTypes = ['merchant', 'user'];
            $userTypes = in_array($typeParam, $allowedTypes) ? [$typeParam] : $allowedTypes;

            $baseQuery = \App\Models\User::query()
                ->whereIn('user_type', $userTypes)
                ->where(function ($query) {
                    $query->whereHas('merchant')
                        ->orWhereHas('customer');
                });

            if (! empty($this->filters['search'])) {
                $searchLower = strtolower(trim($this->filters['search']));
                $baseQuery->where(function ($query) use ($searchLower) {
                    $query->where('first_name', 'like', "%{$searchLower}%")
                        ->orWhere('last_name', 'like', "%{$searchLower}%")
                        ->orWhere('email', 'like', "%{$searchLower}%")
                        ->orWhere('phone_number', 'like', "%{$searchLower}%")
                        ->orWhere('iqama', 'like', "%{$searchLower}%");

                    $query->orWhereHas('merchant', function ($q) use ($searchLower) {
                        $q->where('business_name', 'like', "%{$searchLower}%");
                    });

                    $query->orWhereHas('customer', function ($q) use ($searchLower) {
                        $q->where('business_name', 'like', "%{$searchLower}%");
                    });
                });
            }

            // total count for progress calculation
            $total = (int) $baseQuery->count();

            // ensure initial cache state includes total if known
            Cache::put($cacheKey, [
                'status' => 'processing',
                'progress' => 0,
                'processed' => 0,
                'total' => $total,
            ], 3600);

            if ($total === 0) {
                // nothing to export: create empty file and mark ready
                file_put_contents($filePath, '');
                Cache::put($cacheKey, [
                    'status' => 'ready',
                    'progress' => 100,
                    'processed' => 0,
                    'total' => 0,
                    'filename' => $filename,
                ], 3600);

                return;
            }

            // open file and write header
            $fp = fopen($filePath, 'w');
            if ($fp === false) {
                throw new \RuntimeException("Failed to open file for writing: {$filePath}");
            }

            fputcsv($fp, [
                'User ID',
                'Name',
                'Business Name',
                'CR Number',
                'ID Number',
                'ID/CR Match',
                'ID Expiry Score',
                'CR Expiry Score',
                'Business Type Score',
                'Activity Score',
                'CR Raw Total',
                'CR Weighted Score',
                'POS Revenue',
                'POS Score',
                'Late Payments',
                'Repayment Score',
                'Industry',
                'Industry Score',
                'Location City',
                'Location Tier Score',
                'Location Activity Score',
                'Location Default Rate Score',
                'Location Score',
                'Google Rating',
                'Manual Risk Score',
                'Manual Risk Reason',
                'Total Score',
            ]);

            $processed = 0;
            $chunkSize = 100;

            // debug: log total found
            Log::info("[RiskExport][{$this->exportId}] total users to export: {$total}");
            Cache::put($cacheKey.'_debug', [
                'started_at' => now()->toDateTimeString(),
                'total' => $total,
            ], 3600);

            // Prepare a query clone for chunking. Use chunkById for safety.
            $baseQueryForChunking = clone $baseQuery;
            $baseQueryForChunking->with(['merchant.businessType', 'customer.businessType', 'transactions']);

            $sampleIds = [];

            $baseQueryForChunking->chunkById($chunkSize, function ($users) use (&$processed, $total, $fp, $cacheKey, &$sampleIds) {
                $chunkCount = count($users);

                // collect a few ids for debugging
                foreach ($users as $u) {
                    if (count($sampleIds) < 10) {
                        $sampleIds[] = $u->id;
                    }
                }

                $usersCollection = collect($users);

                // prefetch google ratings for this chunk
                $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers($usersCollection);
                if (! empty($businessNames)) {
                    $this->riskAnalyticsService->prefetchGoogleRatings($businessNames, 10, 200000);
                }

                $risks = $this->riskAnalyticsService->calculateForUsers($usersCollection);

                foreach ($risks as $r) {
                    $location = is_array($r->location) ? $r->location['details'] ?? $r->location : ($r->location ?? []);
                    $city = $location['city'] ?? '-';
                    $tierScore = $location['tier_score'] ?? 0;
                    $activityScore = $location['activity_score'] ?? 0;
                    $defaultRateScore = $location['default_rate_score'] ?? 0;

                    fputcsv($fp, [
                        $r->id,
                        $r->name,
                        $r->business_name,
                        $r->cr_number,
                        $r->id_number,
                        $r->cr_id_match_score,
                        $r->id_expiry_score,
                        $r->cr_expiry_score,
                        $r->business_type_score,
                        $r->activity_score,
                        $r->cr_id_total,
                        $r->cr_id_score,
                        $r->pos_revenue,
                        $r->pos_score,
                        $r->late_payments,
                        $r->repayment_score,
                        $r->industry,
                        $r->industry_score,
                        $city,
                        $tierScore,
                        $activityScore,
                        $defaultRateScore,
                        $r->location_score,
                        $r->google_rating,
                        $r->risk_score,
                        $r->reason,
                        $r->total_score,
                    ]);
                }

                fflush($fp);

                $processed += $chunkCount;

                // compute percent
                $percent = (int) floor(min(100, ($processed / max(1, $total)) * 100));

                // update cache with current progress + processed + total + sample ids
                Cache::put($cacheKey, [
                    'status' => 'processing',
                    'progress' => $percent,
                    'processed' => $processed,
                    'total' => $total,
                ], 3600);

                Cache::put($cacheKey.'_debug_chunk', [
                    'last_chunk_count' => $chunkCount,
                    'processed_so_far' => $processed,
                    'sample_ids' => $sampleIds,
                    'updated_at' => now()->toDateTimeString(),
                ], 3600);

                Log::info("[RiskExport][{$this->exportId}] chunk processed: {$chunkCount}, processed so far: {$processed}");
            });

            fclose($fp);

            // final verify: if processed differs from total, log a warning and store debug info
            if ($processed !== $total) {
                Log::warning("[RiskExport][{$this->exportId}] processed ({$processed}) != total ({$total}). Check query/filter differences.");
                Cache::put($cacheKey.'_debug_final', [
                    'processed' => $processed,
                    'total' => $total,
                    'note' => 'processed != total - possible query mismatch or relationship filtering',
                ], 3600);
            }

            // final mark ready (100%)
            Cache::put($cacheKey, [
                'status' => 'ready',
                'progress' => 100,
                'processed' => $processed,
                'total' => $total,
                'filename' => $filename,
            ], 3600);
        } catch (\Throwable $e) {
            Log::error("Risk export job failed ({$this->exportId}): ".$e->getMessage());
            Cache::put($cacheKey, [
                'status' => 'error',
                'progress' => 0,
                'processed' => $processed ?? 0,
                'total' => $total ?? null,
                'message' => $e->getMessage(),
            ], 3600);
            throw $e;
        }
    }
}
