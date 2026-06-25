<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateRiskExportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RiskExportController extends Controller
{
    /**
     * Start the export job and return export_id (and initial total if available).
     */
    public function startExport(Request $request)
    {
        // collect allowed filters from request (search, type, optionally weights)
        $filters = $request->only(['search', 'type', 'weight_cr_id', 'weight_pos', 'weight_repayment', 'weight_industry', 'weight_location']);

        $exportId = (string) Str::uuid();

        // Try to compute total quickly so frontend can show expected total immediately
        try {
            $typeParam = strtolower($filters['type'] ?? '');
            $allowedTypes = ['merchant', 'user'];
            $userTypes = in_array($typeParam, $allowedTypes) ? [$typeParam] : $allowedTypes;

            $baseQuery = \App\Models\User::query()
                ->whereIn('user_type', $userTypes)
                ->where(function ($query) {
                    $query->whereHas('merchant')
                        ->orWhereHas('customer');
                });

            if (! empty($filters['search'])) {
                $searchLower = strtolower(trim($filters['search']));
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

            $total = (int) $baseQuery->count();
        } catch (\Throwable $e) {
            Log::warning('Failed to compute export total for initial response: '.$e->getMessage());
            $total = null;
        }

        // Initialize cache entry with processing state and known total if computed
        Cache::put($this->cacheKey($exportId), [
            'status' => 'processing',
            'progress' => 0,
            'processed' => 0,
            'total' => $total,
        ], 3600); // 1 hour

        // Dispatch job (make sure queue worker is running)
        GenerateRiskExportJob::dispatch($exportId, $filters);

        return response()->json(['export_id' => $exportId, 'total' => $total]);
    }

    /**
     * Check status.
     */
    public function status($id)
    {
        $cacheKey = $this->cacheKey($id);
        if (! Cache::has($cacheKey)) {
            return response()->json(['ready' => false, 'status' => 'not_found', 'progress' => 0, 'processed' => 0, 'total' => null]);
        }

        $val = Cache::get($cacheKey);

        // If old string-based format remains, handle gracefully
        if (! is_array($val)) {
            if ($val === 'processing') {
                return response()->json(['ready' => false, 'status' => 'processing', 'progress' => 0, 'processed' => 0, 'total' => null]);
            }
            if ($val === 'error') {
                return response()->json(['ready' => false, 'status' => 'error', 'progress' => 0, 'processed' => 0, 'total' => null]);
            }
            // assume filename string
            $fileName = $val;
            $url = route('risk.export.download', ['id' => $id]);

            return response()->json(['ready' => true, 'status' => 'ready', 'progress' => 100, 'processed' => null, 'total' => null, 'url' => $url, 'filename' => $fileName]);
        }

        // expected array format
        $status = $val['status'] ?? 'processing';
        $progress = (int) ($val['progress'] ?? 0);
        $processed = (int) ($val['processed'] ?? 0);
        $total = isset($val['total']) ? $val['total'] : null;

        if ($status === 'processing') {
            return response()->json([
                'ready' => false,
                'status' => 'processing',
                'progress' => $progress,
                'processed' => $processed,
                'total' => $total,
            ]);
        }

        if ($status === 'error') {
            return response()->json([
                'ready' => false,
                'status' => 'error',
                'progress' => $progress,
                'processed' => $processed,
                'total' => $total,
                'message' => ($val['message'] ?? 'Export failed'),
            ]);
        }

        // ready
        $fileName = $val['filename'] ?? null;
        $url = route('risk.export.download', ['id' => $id]);

        return response()->json([
            'ready' => true,
            'status' => 'ready',
            'progress' => 100,
            'processed' => $processed,
            'total' => $total,
            'url' => $url,
            'filename' => $fileName,
        ]);
    }

    /**
     * Download file endpoint.
     */
    public function download($id)
    {
        $cacheKey = $this->cacheKey($id);
        $val = Cache::get($cacheKey);

        if (! $val || (is_array($val) && ($val['status'] ?? '') !== 'ready')) {
            abort(404, 'Export not ready');
        }

        // If it's stored as array, extract filename; else treat as string
        $fileName = is_array($val) ? ($val['filename'] ?? null) : $val;

        if (! $fileName) {
            abort(404, 'Export file not found');
        }

        $filePath = storage_path('app/exports/'.$fileName);

        if (! file_exists($filePath)) {
            abort(404, 'File not found');
        }

        // Stream download and then remove file and cache entry
        // Note: deleteFileAfterSend will remove the file after response is sent
        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }

    private function cacheKey(string $id): string
    {
        return 'risk_export_path_'.$id;
    }
}
