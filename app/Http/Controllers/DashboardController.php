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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class DashboardController extends Controller
{
    public $riskService;
    public function __construct()
    {
        $riskService = RiskAnalyticsService::class;
    }
    public function index(Request $request)
    {
        // dd(Auth::user()->getAllPermissions()->toArray());
        $user = Auth::user();

        // Restrict employees who are not managers
        if ($user->user_type === 'employee' && !$user->is_manager) {
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

        // Format sales
        $categorySales = collect($categorySales)->map(function ($qty, $cat) {
            return ['name' => $cat, 'sales' => $qty];
        })->values();

        // 6. Category Wise Stock
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
        return [
            'disbursement_vs_repayment' => Transaction::getLoanFlowData($range),
            'payment_status' => SchedulePayment::getPaymentStatusDistribution(),
            'overdue_instalments' => SchedulePayment::getOverdueTrend($range),
            'loan_portfolio' => Transaction::getRiskExposureData(),
            'active_loans' => Transaction::where('general_status', 'active')->count()
        ];
    }

    private function getFinancialHealthData($range)
    {
        return [
            'revenue_breakdown' => Order::getRevenueStreams($range),
            'wallet_balances' => Wallet::getBalanceTrends($range),
            'refund_analysis' => RefundRequest::getRefundAnalysis(),
            'cash_flow' => Transaction::getCashFlowData($range)
        ];
    }

    private function getRiskManagementData()
    {
        return [
            'risk_distribution' => RiskManagement::getRiskScoreDistribution(),
            'default_rates' => RiskManagement::getDefaultRatesByBusiness(),
            'credit_scores' => RiskManagement::getAverageCreditScores(),
            'credit_utilization' => CustomerCreditLimit::getCreditUtilization()
        ];
    }

    private function getOperationalMetrics($range)
    {
        return [
            'order_statuses' => Order::getStatusDistribution($range),
            'fulfillment_times' => Order::getFulfillmentTimes($range),
            'payment_methods' => Order::getPaymentMethodDistribution($range),
            'settlement_status' => Transaction::getSettlementStatus()
        ];
    }


    public function getStates($country_id)
    {
        return response()->json(State::where('country_id', $country_id)->get());
    }

    public function getCities($state_id)
    {
        return response()->json(City::where('state_id', $state_id)->get());
    }

    public function home()
    {
        $cities = City::all();
        return view('welcome', compact('cities'));
    }

    public function redirectToPartner($id)
    {
        if (Auth::user()->user_type !== 'admin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        $encryptedUserId = Crypt::encrypt($user->id);

        if (env('APP_ENV') == 'local') {
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

        return redirect()->away($finalUrl);
    }

    public function approvalStore(Request $request)
    {
        // Validate basic input
        $validated = $request->validate([
            'sensitive_permissions' => ['required', 'array', 'min:1'],
            'sensitive_permissions.*' => ['string'],
            'request_reason' => ['required', 'string', 'max:2000'],
        ]);

        $userId = Auth::user()->id;
        $requestedPermissions = $validated['sensitive_permissions'];

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

        return response()->json([
            'success' => true,
            'message' => 'Request submitted successfully.',
            'data' => [
                'id' => $approval->id,
            ],
        ], 201);
    }
}
