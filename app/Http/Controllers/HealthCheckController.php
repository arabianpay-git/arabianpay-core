<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * [PHASE-6] Application health check endpoint.
 *
 * Provides structured health status for monitoring, load balancers,
 * and operational dashboards. No authentication required.
 */
class HealthCheckController extends Controller
{
    public function __invoke()
    {
        $checks = [];
        $healthy = true;

        // Database connectivity
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'latency_ms' => $this->measureDbLatency()];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'fail', 'error' => 'Connection failed'];
            $healthy = false;
        }

        // Cache connectivity
        try {
            Cache::put('health_check_probe', true, 10);
            $checks['cache'] = ['status' => Cache::get('health_check_probe') ? 'ok' : 'fail'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'fail'];
            $healthy = false;
        }

        // Failed jobs count (queue health indicator)
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $recentFailed = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->count();
            $checks['queue'] = [
                'status' => $recentFailed > 10 ? 'degraded' : 'ok',
                'failed_jobs_total' => $failedCount,
                'failed_jobs_24h' => $recentFailed,
            ];
            if ($recentFailed > 10) {
                $healthy = false;
            }
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'unknown', 'error' => 'failed_jobs table not accessible'];
        }

        // Application version/environment
        $checks['app'] = [
            'status' => 'ok',
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];

        $status = $healthy ? 200 : 503;

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $status);
    }

    private function measureDbLatency(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }
}
