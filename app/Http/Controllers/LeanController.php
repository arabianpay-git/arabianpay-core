<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\LeanService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class LeanController extends Controller
{
    protected LeanService $leanService;

    public function __construct(LeanService $leanService)
    {
        $this->leanService = $leanService;
    }

    /**
     * Display Lean dashboard for a customer
     */
    public function index($id)
    {
        // Get customer by user ID
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->first();

        if (!$customer) {
            abort(404, 'Customer not found');
        }

        return view('admin.accounts.lean.index', compact('customer'));
    }

    /**
     * Get banks data
     */
    public function getBanks(Request $request, $id): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'account_types' => 'nullable|string|in:BUSINESS,PERSONAL,CORPORATE,COMMERCIAL,RETAIL,SME',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            // Get account type from query parameter
            $accountType = $request->query('account_types', 'BUSINESS');

            // Fetch banks
            $banks = $this->leanService->getBanks($accountType);

            // Format response
            $data = is_array($banks) && isset($banks['data']) ? $banks['data'] : $banks;

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => is_array($data) ? count($data) : 0,
                    'account_type' => $accountType,
                    'timestamp' => now()->toDateTimeString(),
                ],
                'message' => 'Banks retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch banks: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get entities data
     */
    public function getEntities(Request $request, $id): JsonResponse
    {
        // Validate input - both dates are required in YYYY-MM-DD format
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|date_format:Y-m-d|before_or_equal:end_date',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            // Get parameters
            // $startDate = $request->query('start_date');
            // $endDate = $request->query('end_date');
            $page = (int) $request->query('page', 0);
            $size = (int) $request->query('size', 50);

            $customerId = "d6933bfe-4fad-4d98-a1cc-67a382f507a6";
            // Fetch entities
            $entities = $this->leanService->getEntitiesByCustomerId($customerId, $page, $size);
            // $entities = $this->leanService->getEntities($startDate, $endDate, $page, $size);

            // Format response
            $data = is_array($entities) && isset($entities['data']) ? $entities['data'] : $entities;
            $pagination = is_array($entities) && isset($entities['page']) ? $entities['page'] : null;

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => $pagination['total_elements'] ?? (is_array($data) ? count($data) : 0),
                    'page' => $pagination['number'] ?? $page,
                    'size' => $pagination['size'] ?? $size,
                    // 'start_date' => $startDate,
                    // 'end_date' => $endDate,
                    'timestamp' => now()->toDateTimeString(),
                ],
                'message' => 'Entities retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch entities: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Test Lean API connection
     */
    public function testConnection(): JsonResponse
    {
        try {
            $connected = $this->leanService->testConnection();

            return response()->json([
                'success' => $connected,
                'message' => $connected
                    ? 'Successfully connected to Lean Open Banking API'
                    : 'Failed to connect to Lean Open Banking API',
                'environment' => config('lean.environment'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear token cache
     */
    public function clearCache(): JsonResponse
    {
        try {
            $cleared = $this->leanService->clearTokenCache();

            return response()->json([
                'success' => $cleared,
                'message' => $cleared
                    ? 'Token cache cleared successfully'
                    : 'Failed to clear token cache',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cache clear failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get bank statement report
     */
    public function getBankStatement(Request $request, $id, $reportId): JsonResponse
    {
        try {

            // Fetch bank statement report
            $report = $this->leanService->getBankStatementReportById('f53e1ed9-b4a0-4326-8e26-141ffd3a8824', $reportId ?? '40de217f-866e-4b3f-9ded-3b54024b1323');

            // Format response
            return response()->json([
                'success' => true,
                'data' => $report,
                'meta' => [
                    'report_id' => $reportId,
                    'customer_id' => $id,
                    'status' => $report['status'] ?? 'UNKNOWN',
                    'created_at' => $report['created_at'] ?? null,
                    'timestamp' => now()->toDateTimeString(),
                ],
                'message' => 'Bank statement report retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bank statement report: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
