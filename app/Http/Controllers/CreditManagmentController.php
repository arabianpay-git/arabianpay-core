<?php

namespace App\Http\Controllers;

use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditManagmentController extends Controller
{
    protected $creditAssesmentService;
    protected $riskAnalyticsService;
    protected $riskService;
    protected $auditTrailService;

    public function __construct(
        CreditAssessmentService $creditAssesmentService,
        RiskAnalyticsService $riskAnalyticsService,
        RiskService $riskService,
        AuditTrailService $auditTrailService
    ) {
        $this->creditAssesmentService = $creditAssesmentService;
        $this->riskAnalyticsService = $riskAnalyticsService;
        $this->riskService = $riskService;
        $this->auditTrailService = $auditTrailService;
    }

    private function calculateTotalOrderAmount($orders): float
    {
        $total = 0;

        foreach ($orders as $order) {
            $items    = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax      = calculate_order_tax($order);

            $base = $subTotal + $tax + $shipping - $discount;

            $commissionPct    = get_system_commission();
            $commissionAmount = $base * ($commissionPct / 100);

            $commissionTaxPct  = get_commission_tax();
            $commissionTaxAmt  = $commissionAmount * ($commissionTaxPct / 100);

            $total += $base + $commissionAmount + $commissionTaxAmt;
        }

        return $total;
    }

    private function getCustomersWithCreditData($search = null, $perPage = 10)
    {
        $query = User::where('user_type', 'user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('iqama', 'like', "%{$search}%");
            });
        }

        $customers = $query->with([
            'customerCreditLimit',
            'orders' => function ($q) {
                $q->where('delivery_status', 'delivered');
            },
        ])->paginate($perPage);

        foreach ($customers as $customer) {
            try {
                $orders = $customer->orders ?? collect();
                $customer->total_used = $this->calculateTotalOrderAmount($orders);

                $creditScoreService = $this->creditAssesmentService->assess($customer->id);
                $riskScoreService = $this->riskService->analyzeCustomer($customer->customer, 'customer');
                // $riskScoreService = $this->riskAnalyticsService->calculateForUser($customer);

                $creditScore = $creditScoreService['creditScore']['compositeScore'] ?? 0;
                $riskScore = $riskScoreService['omrs'] ?? 0;

                $oldCreditLimit = 20000;

                $finalScore = $creditScore * ($riskScore / 100);
                $newCreditLimit = $oldCreditLimit * ($finalScore / 100);

                $remainingCreditLimit = max(0, $newCreditLimit - $customer->total_used);

                $customer->creditLimit = $newCreditLimit;
                $customer->limit_remaining = $remainingCreditLimit;
                $customer->repayment_history = SchedulePayment::where('user_id', $customer->id)
                    ->where('payment_status', 'paid')
                    ->sum('instalment_amount');

                $customer->credit_score = round($creditScore);
            } catch (\Throwable $e) {
                // Log credit data calculation error
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'credit_data_calculation_failed',
                    'entity_type' => 'User',
                    'entity_id' => $customer->id,
                    'action_summary' => "Failed to calculate credit data for user '{$customer->first_name} {$customer->last_name}'",
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'user_id' => $customer->id,
                        'email' => $customer->email,
                    ],
                ]);

                $customer->creditLimit = 0;
                $customer->limit_remaining = 0;
                $customer->repayment_history = 0;
                $customer->credit_score = 0;
                $customer->total_used = 0;
            }
        }

        return $customers;
    }

    public function creditProfile(Request $request)
    {
        if (!hasSensitivePermission('credit_decision_output')) {
            // Log unauthorized access attempt to credit profiles
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_credit_profile_access',
                'entity_type' => 'CreditProfile',
                'action_summary' => 'Attempted to access credit profiles without required permission',
                'properties' => [
                    'permission_required' => 'credit_decision_output',
                    'search_query' => $request->input('search'),
                    'ip_address' => $request->ip(),
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        // Log successful access to credit profiles
        $search = $request->input('search');
        $this->auditTrailService->logViewOperation(
            'view_credit_profiles',
            'CreditProfile',
            'Viewed credit profiles',
            [
                'search_query' => $search,
                'per_page' => 10,
                'has_permission' => true,
                'permission_name' => 'credit_decision_output',
            ]
        );

        $customers = $this->getCustomersWithCreditData($search, 10);
        return view('admin.credit-managment.profiles', compact('customers'));
    }

    public function creditLimit(Request $request)
    {
        $search = $request->input('search');

        // Log view credit limits
        $this->auditTrailService->logViewOperation(
            'view_credit_limits',
            'CreditLimit',
            'Viewed credit limits',
            [
                'search_query' => $search,
                'per_page' => 10,
                'access_type' => 'sensitive_data',
                'pii_fields_involved' => ['credit_limit', 'credit_score'],
            ]
        );

        $creditLimits = $this->getCustomersWithCreditData($search, 10);

        // Log detailed credit limit access with justification
        $this->auditTrailService->log([
            'event_category' => 'risk_management',
            'event_type' => 'credit_limit_review',
            'entity_type' => 'CreditLimit',
            'action_summary' => 'Performed credit limit review for multiple customers',
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['credit_limit', 'credit_score', 'financial_data'],
            'properties' => [
                'total_customers_reviewed' => $creditLimits->total(),
                'search_used' => !empty($search),
                'average_credit_score' => $creditLimits->isNotEmpty() ?
                    round($creditLimits->avg('credit_score'), 2) : 0,
                'total_credit_exposure' => $creditLimits->isNotEmpty() ?
                    number_format($creditLimits->sum('creditLimit'), 2) : '0.00',
            ],
        ]);

        return view('admin.credit-managment.limits', ['creditLimits' => $creditLimits]);
    }

    public function repaymentSchedule(Request $request)
    {
        $search = $request->input('search');

        // Log view repayment schedules
        $this->auditTrailService->logViewOperation(
            'view_repayment_schedules',
            'RepaymentSchedule',
            'Viewed repayment schedules',
            [
                'search_query' => $search,
                'per_page' => 10,
                'order_by' => 'due_date_asc',
                'access_type' => 'financial_data',
            ]
        );

        $schedulePayments = SchedulePayment::with(['assigned', 'user'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where(function ($s) use ($search) {
                        $s->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('iqama', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('due_date', 'asc')
            ->paginate(10)
            ->appends(['search' => $search]);

        // Log detailed repayment schedule analysis
        $totalPayments = $schedulePayments->total();
        $totalAmount = $schedulePayments->sum('instalment_amount');
        $statusBreakdown = $schedulePayments->groupBy('payment_status')->map->count();

        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'repayment_schedule_analysis',
            'entity_type' => 'RepaymentSchedule',
            'action_summary' => 'Analyzed repayment schedules with financial data',
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['payment_data', 'customer_information'],
            'properties' => [
                'total_payments' => $totalPayments,
                'total_amount' => number_format($totalAmount, 2),
                'status_breakdown' => $statusBreakdown,
                'search_applied' => !empty($search),
                'search_term' => $search,
                'date_range' => [
                    'earliest_due_date' => $schedulePayments->isNotEmpty() ?
                        $schedulePayments->first()->due_date->format('Y-m-d') : null,
                    'latest_due_date' => $schedulePayments->isNotEmpty() ?
                        $schedulePayments->last()->due_date->format('Y-m-d') : null,
                ],
            ],
        ]);

        return view('admin.credit-managment.repayment', compact('schedulePayments'));
    }

    /**
     * View detailed credit assessment for a specific customer
     */
    public function customerCreditAssessment($id)
    {
        if (!hasSensitivePermission('credit_decision_output')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_detailed_credit_access',
                'entity_type' => 'CreditAssessment',
                'entity_id' => $id,
                'action_summary' => "Attempted to access detailed credit assessment for customer ID: {$id} without permission",
                'properties' => [
                    'permission_required' => 'credit_decision_output',
                    'customer_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $customer = User::with('customer')->findOrFail($id);

        try {
            // Perform credit assessment
            $creditScoreService = $this->creditAssesmentService->assess($customer->id);
            $riskScoreService = $this->riskService->analyzeCustomer($customer->customer, 'customer');

            $creditScore = $creditScoreService['creditScore']['compositeScore'] ?? 0;
            $riskScore = $riskScoreService['omrs'] ?? 0;

            // Log detailed credit assessment access
            $justificationData = $this->auditTrailService->withJustification(
                'Credit assessment performed for risk management decision making',
                'risk_management',
                ['credit_score', 'risk_score', 'financial_data', 'personal_data']
            );

            $this->auditTrailService->log([
                'event_category' => 'risk_management',
                'event_type' => 'detailed_credit_assessment',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'action_summary' => "Performed detailed credit assessment for '{$customer->first_name} {$customer->last_name}'",
                'pdpl_category' => 'legal_obligation',
                'pii_fields_involved' => ['credit_score', 'risk_score', 'personal_data', 'financial_history'],
                'properties' => array_merge([
                    'customer_id' => $customer->id,
                    'customer_name' => "{$customer->first_name} {$customer->last_name}",
                    'customer_email' => $customer->email,
                    'credit_score' => round($creditScore, 2),
                    'risk_score' => round($riskScore, 2),
                    'assessment_timestamp' => now()->toISOString(),
                    'assessment_methodology' => 'composite_scoring',
                    'credit_components' => $creditScoreService['creditScore'] ?? [],
                    'risk_components' => $riskScoreService,
                ], $justificationData),
            ]);

            // You can return a view with detailed assessment data
            return view('admin.credit-managment.detailed-assessment', compact(
                'customer',
                'creditScoreService',
                'riskScoreService'
            ));
        } catch (\Exception $e) {
            // Log credit assessment failure
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'credit_assessment_failed',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'action_summary' => "Failed to perform credit assessment for '{$customer->first_name} {$customer->last_name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'attempted_assessment_type' => 'detailed',
                ],
            ]);

            return back()->with('error', 'Failed to perform credit assessment: ' . $e->getMessage());
        }
    }

    /**
     * Update credit limit for a customer
     */
    public function updateCreditLimit(Request $request, $id)
    {
        if (!hasSensitivePermission('credit_decision_output')) {
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_credit_limit_update',
                'entity_type' => 'CustomerCreditLimit',
                'entity_id' => $id,
                'action_summary' => "Attempted to update credit limit for customer ID: {$id} without permission",
                'properties' => [
                    'permission_required' => 'credit_decision_output',
                    'customer_id' => $id,
                    'requested_limit' => $request->input('credit_limit'),
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $request->validate([
            'credit_limit' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $customer = User::with('customer')->findOrFail($id);
        $oldCreditLimit = $customer->customerCreditLimit->limit_arabianpay_after ?? 0;

        try {
            // Update credit limit logic here (using your CustomerCreditLimit model)
            // For example:
            // $customer->customerCreditLimit()->updateOrCreate(
            //     ['user_id' => $customer->id],
            //     ['limit_arabianpay_after' => $request->credit_limit]
            // );

            // Log credit limit update with justification
            $justificationData = $this->auditTrailService->withJustification(
                $request->reason,
                'risk_management',
                ['credit_limit']
            );

            $this->auditTrailService->logUpdated(
                $customer, // or your credit limit model instance
                ['credit_limit' => $oldCreditLimit],
                "Updated credit limit for '{$customer->first_name} {$customer->last_name}' from SAR " .
                    number_format($oldCreditLimit, 2) . " to SAR " . number_format($request->credit_limit, 2),
                $justificationData
            );

            return back()->with('success', 'Credit limit updated successfully.');
        } catch (\Exception $e) {
            // Log credit limit update failure
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'credit_limit_update_failed',
                'entity_type' => 'CustomerCreditLimit',
                'entity_id' => $id,
                'action_summary' => "Failed to update credit limit for '{$customer->first_name} {$customer->last_name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'customer_id' => $customer->id,
                    'old_limit' => $oldCreditLimit,
                    'new_limit_requested' => $request->credit_limit,
                    'reason_provided' => $request->reason,
                ],
            ]);

            return back()->with('error', 'Failed to update credit limit: ' . $e->getMessage());
        }
    }

    /**
     * Search customers for credit management
     */
    public function searchCustomers(Request $request)
    {
        $search = $request->input('search', '');

        // Log search operation in credit management
        $this->auditTrailService->logSearch(
            'CustomerCredit',
            $search,
            User::where('user_type', 'user')->count(),
            [
                'properties' => [
                    'search_type' => 'credit_management',
                    'search_fields' => ['name', 'email', 'phone', 'iqama'],
                    'access_type' => 'sensitive_data',
                    'permission_required' => 'credit_decision_output',
                    'has_permission' => hasSensitivePermission('credit_decision_output'),
                ],
            ]
        );

        $customers = User::where('user_type', 'user')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('iqama', 'like', "%{$search}%");
                });
            })
            ->select(['id', 'first_name', 'last_name', 'email', 'phone_number', 'created_at'])
            ->limit(50)
            ->get();

        if ($request->ajax()) {
            return response()->json(['customers' => $customers]);
        }

        return view('admin.credit-managment.search-results', compact('customers', 'search'));
    }
}
