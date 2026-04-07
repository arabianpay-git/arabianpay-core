<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\RiskManagement;
use App\Models\SchedulePayment;
use App\Models\RefundRequest;
use App\Models\CustomerCreditLimit;
use App\Models\Product;
use App\Models\SensitiveDataApproval;
use App\Models\State;
use App\Models\User;
use App\Models\Wallet;
use App\Services\RiskAnalyticsService;
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $riskService;
    protected $auditTrailService;

    public function __construct(RiskAnalyticsService $riskService, AuditTrailService $auditTrailService)
    {
        $this->riskService = $riskService;
        $this->auditTrailService = $auditTrailService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Log dashboard access with appropriate context
        $this->auditTrailService->log([
            'event_category' => 'dashboard_operations',
            'event_type' => 'dashboard_access',
            'entity_type' => 'Dashboard',
            'action_summary' => 'User accessed dashboard',
            'properties' => [
                'user_type' => $user->user_type,
                'is_manager' => $user->is_manager,
                'date_range' => $request->input('date_range', '12M')
            ]
        ]);

        // Restrict employees who are not managers
        if ($user->user_type === 'employee' && !$user->is_manager) {
            // Log restricted access attempt
            $this->auditTrailService->log([
                'event_category' => 'access_control',
                'event_type' => 'access_denied',
                'entity_type' => 'Dashboard',
                'action_summary' => 'Non-manager employee attempted to access admin dashboard',
                'properties' => [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type,
                    // 'permissions' => $user->getAllPermissions()->pluck('name')->toArray()
                ]
            ]);

            return view('admin.dashboard.employee');
        }

        $dateRange = $request->input('date_range', '12M');

        // 1. Loan Performance Charts
        $loanData = $this->getLoanPerformanceData($dateRange);

        // 2. Financial Health Charts
        $financialData = $this->getFinancialHealthData($dateRange);

        // 3. Risk Management Charts
        $riskData = $this->getRiskManagementData();

        // 4. Operational Metrics
        $operationalData = $this->getOperationalMetrics($dateRange);

        // 5. Category Wise Sales
        $categorySales = $this->getCategorySalesData();

        // 6. Category Wise Stock
        $categoryStock = $this->getCategoryStockData();

        // Log successful dashboard access with data summary
        $this->auditTrailService->log([
            'event_category' => 'dashboard_operations',
            'event_type' => 'dashboard_data_loaded',
            'entity_type' => 'Dashboard',
            'action_summary' => 'Dashboard data loaded successfully',
            'properties' => [
                'date_range' => $dateRange,
                'loan_data_available' => !empty($loanData),
                'financial_data_available' => !empty($financialData),
                'risk_data_available' => !empty($riskData),
                'operational_data_available' => !empty($operationalData),
                'categories_count' => $categorySales->count(),
                'stock_categories_count' => $categoryStock->count()
            ]
        ]);

        return view('admin.dashboard.index', compact(
            'loanData',
            'financialData',
            'riskData',
            'operationalData',
            'dateRange',
            'categorySales',
            'categoryStock',
        ));
    }

    private function getLoanPerformanceData($range)
    {
        try {
            $data = [
                'disbursement_vs_repayment' => Transaction::getLoanFlowData($range),
                'payment_status' => SchedulePayment::getPaymentStatusDistribution(),
                'overdue_instalments' => SchedulePayment::getOverdueTrend($range),
                'loan_portfolio' => Transaction::getRiskExposureData(),
                'active_loans' => Transaction::where('general_status', 'active')->count()
            ];

            // Log loan data retrieval (once, not per query)
            $this->auditTrailService->logViewOperation(
                'loan_performance_report',
                'LoanPerformance',
                'Retrieved loan performance dashboard data',
                [
                    'date_range' => $range,
                    'data_points_count' => count($data),
                    'active_loans_count' => $data['active_loans']
                ]
            );

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to get loan performance data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getFinancialHealthData($range)
    {
        try {
            $data = [
                'revenue_breakdown' => Order::getRevenueStreams($range),
                'wallet_balances' => Wallet::getBalanceTrends($range),
                'refund_analysis' => RefundRequest::getRefundAnalysis(),
                'cash_flow' => Transaction::getCashFlowData($range)
            ];

            // Log financial data retrieval
            $this->auditTrailService->logViewOperation(
                'financial_health_report',
                'FinancialHealth',
                'Retrieved financial health dashboard data',
                [
                    'date_range' => $range,
                    'data_points_count' => count($data)
                ]
            );

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to get financial health data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getRiskManagementData()
    {
        try {
            $data = [
                'risk_distribution' => RiskManagement::getRiskScoreDistribution(),
                'default_rates' => RiskManagement::getDefaultRatesByBusiness(),
                'credit_scores' => RiskManagement::getAverageCreditScores(),
                'credit_utilization' => CustomerCreditLimit::getCreditUtilization()
            ];

            // Log risk data retrieval
            $this->auditTrailService->logViewOperation(
                'risk_management_report',
                'RiskManagement',
                'Retrieved risk management dashboard data',
                [
                    'data_points_count' => count($data)
                ]
            );

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to get risk management data', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getOperationalMetrics($range)
    {
        try {
            $data = [
                'order_statuses' => Order::getStatusDistribution($range),
                'fulfillment_times' => Order::getFulfillmentTimes($range),
                'payment_methods' => Order::getPaymentMethodDistribution($range),
                'settlement_status' => Transaction::getSettlementStatus()
            ];

            // Log operational data retrieval
            $this->auditTrailService->logViewOperation(
                'operational_metrics_report',
                'OperationalMetrics',
                'Retrieved operational metrics dashboard data',
                [
                    'date_range' => $range,
                    'data_points_count' => count($data)
                ]
            );

            return $data;
        } catch (\Exception $e) {
            Log::error('Failed to get operational metrics', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getCategorySalesData()
    {
        try {
            $categorySales = [];

            foreach (Order::all() as $order) {
                $details = json_decode($order->product_details, true);

                foreach ($details as $item) {
                    $product = Product::with('category')->find($item['product_id']);
                    if ($product && $product->category) {
                        $categoryName = $product->category->name;
                        $quantity = $item['quantity'] ?? 0;

                        if (!isset($categorySales[$categoryName])) {
                            $categorySales[$categoryName] = 0;
                        }

                        $categorySales[$categoryName] += $quantity;
                    }
                }
            }

            $formattedData = collect($categorySales)->map(function ($qty, $cat) {
                return ['name' => $cat, 'sales' => $qty];
            })->values();

            // Log category sales retrieval
            $this->auditTrailService->logViewOperation(
                'category_sales_report',
                'CategorySales',
                'Retrieved category-wise sales data',
                [
                    'categories_count' => $formattedData->count(),
                    'total_sales' => array_sum($categorySales)
                ]
            );

            return $formattedData;
        } catch (\Exception $e) {
            Log::error('Failed to get category sales data', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    private function getCategoryStockData()
    {
        try {
            $categoryStock = Category::with('products')->get()
                ->map(function ($category) {
                    $stock = $category->products->sum('current_stock');
                    return [
                        'name' => $category->name,
                        'stock' => $stock,
                    ];
                })
                ->sortByDesc('stock')
                ->take(12)
                ->values();

            // Log category stock retrieval
            $this->auditTrailService->logViewOperation(
                'category_stock_report',
                'CategoryStock',
                'Retrieved category-wise stock data',
                [
                    'categories_count' => $categoryStock->count(),
                    'total_stock' => $categoryStock->sum('stock')
                ]
            );

            return $categoryStock;
        } catch (\Exception $e) {
            Log::error('Failed to get category stock data', ['error' => $e->getMessage()]);
            return collect([]);
        }
    }

    public function getStates(Request $request, $country_id)
    {
        try {
            $states = State::where('country_id', $country_id)->get();

            // Log state lookup with justification for viewing location data
            $justificationData = $this->auditTrailService->withJustification(
                'Location data required for address selection in the application',
                'legitimate_interest',
                ['country_id', 'state_id', 'state_name']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'lookup_operations',
                'event_type' => 'state_lookup',
                'entity_type' => 'State',
                'action_summary' => 'Retrieved states for country',
                'properties' => [
                    'country_id' => $country_id,
                    'states_count' => $states->count(),
                    'request_source' => $request->fullUrl()
                ]
            ], $justificationData));

            return response()->json($states);
        } catch (\Exception $e) {
            Log::error('Failed to get states', ['error' => $e->getMessage(), 'country_id' => $country_id]);
            return response()->json([], 500);
        }
    }

    public function getCities(Request $request, $state_id)
    {
        try {
            $cities = City::where('state_id', $state_id)->get();

            // Log city lookup with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Location data required for address selection in the application',
                'legitimate_interest',
                ['state_id', 'city_id', 'city_name']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'lookup_operations',
                'event_type' => 'city_lookup',
                'entity_type' => 'City',
                'action_summary' => 'Retrieved cities for state',
                'properties' => [
                    'state_id' => $state_id,
                    'cities_count' => $cities->count(),
                    'request_source' => $request->fullUrl()
                ]
            ], $justificationData));

            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Failed to get cities', ['error' => $e->getMessage(), 'state_id' => $state_id]);
            return response()->json([], 500);
        }
    }

    public function home()
    {
        try {
            $cities = City::all();

            // Log home page access
            $this->auditTrailService->log([
                'event_category' => 'public_access',
                'event_type' => 'homepage_access',
                'entity_type' => 'Website',
                'action_summary' => 'User accessed public homepage',
                'properties' => [
                    'cities_count' => $cities->count(),
                    'is_authenticated' => Auth::check(),
                    'user_type' => Auth::check() ? Auth::user()->user_type : 'guest'
                ]
            ]);

            return view('welcome', compact('cities'));
        } catch (\Exception $e) {
            Log::error('Failed to load home page', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function redirectToPartner(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->user_type !== 'admin') {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'access_control',
                'event_type' => 'unauthorized_impersonation_attempt',
                'entity_type' => 'User',
                'entity_id' => $id,
                'action_summary' => 'Non-admin user attempted partner impersonation',
                'properties' => [
                    'attempted_by' => $user->id,
                    'attempted_by_email' => $user->email,
                    'target_user_id' => $id,
                    'user_type' => $user->user_type
                ]
            ]);

            abort(403, 'Unauthorized action.');
        }

        try {
            $targetUser = User::findOrFail($id);

            $encryptedUserId = Crypt::encrypt($targetUser->id);

            if (config('app.env') === 'local') { // [PHASE-5] migrated to config
                $baseUrl = 'https://merchant.test/impersonate-login';
            } else {
                $baseUrl = 'https://partners.arabianpay.net/impersonate-login';
            }

            $params = [
                'token' => $encryptedUserId,
                'expires' => now()->addMinutes(2)->timestamp,
            ];

            $signature = hash_hmac(
                'sha256',
                '/impersonate-login?token=' . urlencode($params['token']) . '&expires=' . $params['expires'],
                config('app.key')
            );

            $params['signature'] = $signature;

            $finalUrl = $baseUrl . '?' . http_build_query($params);

            // Log successful impersonation with security justification
            $justificationData = $this->auditTrailService->withJustification(
                'Admin impersonation for troubleshooting and support purposes',
                'legal_obligation',
                ['user_id', 'user_email']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'security_operations',
                'event_type' => 'user_impersonation',
                'entity_type' => 'User',
                'entity_id' => $targetUser->id,
                'action_summary' => 'Admin impersonated partner user',
                'before_state' => null,
                'after_state' => [
                    'impersonated_by' => $user->id,
                    'impersonated_at' => now()->toISOString(),
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email
                ],
                'properties' => [
                    'impersonator_id' => $user->id,
                    'impersonator_email' => $user->email,
                    'target_user_id' => $targetUser->id,
                    'target_user_email' => $targetUser->email,
                    'expiry_time' => now()->addMinutes(2)->toISOString(),
                    'redirect_url' => $baseUrl
                ]
            ], $justificationData));

            return redirect()->away($finalUrl);
        } catch (\Exception $e) {
            // Log failed impersonation attempt
            $this->auditTrailService->log([
                'event_category' => 'security_operations',
                'event_type' => 'impersonation_failed',
                'entity_type' => 'User',
                'entity_id' => $id,
                'action_summary' => 'Partner impersonation failed',
                'properties' => [
                    'error' => $e->getMessage(),
                    'attempted_by' => $user->id,
                    'attempted_by_email' => $user->email,
                    'target_user_id' => $id
                ]
            ]);

            Log::error('Failed to redirect to partner', [
                'error' => $e->getMessage(),
                'admin_id' => $user->id,
                'target_user_id' => $id
            ]);

            abort(404, 'User not found or operation failed.');
        }
    }

    public function approvalStore(Request $request)
    {
        $user = Auth::user();

        // Validate basic input
        $validated = $request->validate([
            'sensitive_permissions' => ['required', 'array', 'min:1'],
            'sensitive_permissions.*' => ['string'],
            'request_reason' => ['required', 'string', 'max:2000'],
        ]);

        $userId = $user->id;
        $requestedPermissions = $validated['sensitive_permissions'];

        try {
            // Get all pending approvals of this user
            $pendingApprovals = SensitiveDataApproval::where('requested_by', $userId)
                ->where('status', 'pending')
                ->get();

            // Flatten all sensitive_permissions from pending approvals
            $alreadyRequested = [];
            foreach ($pendingApprovals as $approval) {
                $alreadyRequested = array_merge($alreadyRequested, $approval->sensitive_permissions ?? []);
            }
            $alreadyRequested = array_unique($alreadyRequested);

            // Find intersection with current request
            $conflictingPermissions = array_intersect($requestedPermissions, $alreadyRequested);

            if (!empty($conflictingPermissions)) {
                $permissionList = implode(', ', $conflictingPermissions);

                // Log conflicting permission request attempt
                $this->auditTrailService->log([
                    'event_category' => 'approval_operations',
                    'event_type' => 'duplicate_permission_request',
                    'entity_type' => 'SensitiveDataApproval',
                    'action_summary' => 'User attempted to request already pending sensitive permissions',
                    'properties' => [
                        'user_id' => $userId,
                        'conflicting_permissions' => $conflictingPermissions,
                        'request_reason' => $validated['request_reason'],
                        'pending_approvals_count' => $pendingApprovals->count()
                    ]
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "You already have an active request for the following sensitive permissions: $permissionList.",
                ], 422);
            }

            // Create new approval record
            $approval = SensitiveDataApproval::create([
                'requested_by' => $userId,
                'sensitive_permissions' => $requestedPermissions,
                'request_reason' => $validated['request_reason'] ?? null,
                'status' => 'pending',
            ]);

            // Log successful approval request creation
            $justificationData = $this->auditTrailService->withJustification(
                $validated['request_reason'],
                'legal_obligation',
                $requestedPermissions
            );

            $this->auditTrailService->logCreated(
                $approval,
                "User requested sensitive permissions: " . implode(', ', $requestedPermissions),
                array_merge([
                    'event_category' => 'approval_operations',
                    'event_type' => 'sensitive_permission_request',
                ], $justificationData)
            );

            return response()->json([
                'success' => true,
                'message' => 'Request submitted successfully.',
                'data' => [
                    'id' => $approval->id,
                ],
            ], 201);
        } catch (\Exception $e) {
            // Log approval request failure
            $this->auditTrailService->log([
                'event_category' => 'approval_operations',
                'event_type' => 'permission_request_failed',
                'entity_type' => 'SensitiveDataApproval',
                'action_summary' => 'Failed to submit sensitive permission request',
                'properties' => [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                    'requested_permissions' => $requestedPermissions
                ]
            ]);

            Log::error('Failed to store approval request', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your request.',
            ], 500);
        }
    }
}
