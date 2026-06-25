<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCustomerStatusRequest;
use App\Http\Requests\UpgradeCustomerPackageRequest;
use App\Models\CrValidation;
use App\Models\Customer;
use App\Models\CustomerCreditLimit;
use App\Models\NafathVerification;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AuditTrailService;
use App\Services\CreditAssessmentService;
use App\Services\FirebaseService;
use App\Services\RiskAnalyticsService;
use App\Services\WathqService;
use App\Traits\EmailSender;
use App\Traits\SmsSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class AccountController extends Controller
{
    use EmailSender, SmsSender;

    protected $wathqService;

    protected $firebase;

    protected $auditTrailService;

    public function __construct(WathqService $wathqService, FirebaseService $firebase, AuditTrailService $auditTrailService)
    {
        $this->wathqService = $wathqService;
        $this->firebase = $firebase;
        $this->auditTrailService = $auditTrailService;
    }

    public function customers(Request $request)
    {
        $user = currentUser();

        // Log view customers list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Customer',
            'Viewed customers list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
                'user_type' => $user->user_type,
                'is_manager' => $user->is_manager ?? false,
            ]
        );

        $customers = Customer::with([
            'assigned',
            'user',
            'package',
            'user.orders' => function ($q) {
                $q->where('delivery_status', 'delivered');
            },
        ])
            ->select(['id', 'assigned_to', 'user_id', 'package_id', 'cr_number', 'address', 'purchasing_volume', 'status', 'created_at'])
            ->when(
                ! (
                    $user->user_type === 'employee' && $user->is_manager
                ) && $user->user_type !== 'admin',
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->orderByDesc('id')
            ->orderByRaw('ISNULL(assigned_to) DESC')
            ->paginate(10);

        $totalOrderAmount = 0;

        if ($customers->isNotEmpty()) {
            foreach ($customers as $customer) {
                $orders = $customer->user->orders ?? collect();
                $totalOrderAmount += $this->calculateTotalOrderAmount($orders);
            }
        }

        return view('admin.accounts.customer', compact('customers', 'totalOrderAmount'));
    }

    public function log($id)
    {
        $customer = Customer::with('user')->where('user_id', $id)->firstOrFail();

        // Log view customer activity logs
        $this->auditTrailService->log([
            'event_category' => 'audit_operations',
            'event_type' => 'view_activity_logs',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed activity logs for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'properties' => [
                'customer_id' => $customer->id,
                'user_id' => $customer->user_id,
            ],
        ]);

        $logs = Activity::where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('admin.accounts.customer-log', compact('customer', 'logs'));
    }

    public function customerBusiness()
    {
        // Log view customer business
        $this->auditTrailService->logViewOperation(
            'view_customer_business',
            'Customer',
            'Accessed customer business section'
        );

        // TODO: implement customer business view [CORE-P0-04]
    }

    public function customerSimah($id)
    {
        $customer = Customer::with('user')->where('user_id', $id)->firstOrFail();

        if (! hasSensitivePermission('credit_data_simah_bureau')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_access_attempt',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'action_summary' => "Attempted to access SIMAH data without permission for customer '{$customer->user->first_name} {$customer->user->last_name}'",
                'properties' => [
                    'permission_required' => 'credit_data_simah_bureau',
                    'customer_id' => $customer->id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        // Log access to SIMAH data
        $this->auditTrailService->log([
            'event_category' => 'sensitive_access',
            'event_type' => 'view_simah_data',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Accessed SIMAH data for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['cr_number', 'credit_data'],
            'properties' => [
                'customer_id' => $customer->id,
                'has_permission' => true,
            ],
        ]);

        return view('admin.accounts.customer-simah', compact('customer'));
    }

    public function customerProfile($id, CreditAssessmentService $creditService, RiskAnalyticsService $riskService)
    {
        $user = currentUser();

        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->when(
                ! (
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (! $customer) {
            // Log failed access attempt
            $this->auditTrailService->log([
                'event_category' => 'access_control',
                'event_type' => 'customer_access_denied',
                'entity_type' => 'Customer',
                'action_summary' => 'Attempted to access customer profile without proper assignment or permissions',
                'properties' => [
                    'attempted_user_id' => $id,
                    'current_user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'is_manager' => $user->is_manager ?? false,
                ],
            ]);

            return redirect()->route('customers')->with('error', __('Customer not found or not assigned to you.'));
        }

        // Log view customer profile
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_customer_profile',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed profile for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'properties' => [
                'customer_id' => $customer->id,
                'customer_status' => $customer->status,
                'risk_assessment_performed' => true,
            ],
        ]);

        $data = $creditService->assess($id);
        $riskScore = $riskService->calculateForUser($customer->user);

        if (empty($customer->cr_data) && $customer->cr_number) {
            $wathqData = $this->wathqService->fetchCrData($customer->cr_number);

            if ($wathqData) {
                $customer->cr_data = $wathqData;
                $customer->save();

                // Log CR data fetch
                $this->auditTrailService->log([
                    'event_category' => 'data_sync',
                    'event_type' => 'cr_data_fetch',
                    'entity_type' => 'Customer',
                    'entity_id' => $customer->id,
                    'action_summary' => "Fetched CR data from Wathq for customer '{$customer->user->first_name} {$customer->user->last_name}'",
                    'properties' => [
                        'cr_number' => $customer->cr_number,
                        'data_source' => 'wathq',
                        'success' => true,
                    ],
                ]);
            }
        }

        return view('admin.accounts.customer-profile', compact('customer', 'data', 'riskScore'));
    }

    public function customerFinance($id, CreditAssessmentService $creditService, RiskAnalyticsService $riskService)
    {
        if (! hasSensitivePermission('transaction_references')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_finance_access',
                'entity_type' => 'Customer',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access customer finance data without permission',
                'properties' => [
                    'permission_required' => 'transaction_references',
                    'customer_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $customer = Customer::with('user', 'package')
            ->where('user_id', $id)
            ->firstOrFail();

        // Log view customer finance
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_customer_finance',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed finance details for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['financial_data'],
            'properties' => [
                'customer_id' => $customer->id,
                'credit_assessment_performed' => true,
                'risk_calculation_performed' => true,
            ],
        ]);

        $data = $creditService->assess($id);
        $riskScore = $riskService->calculateForUser($customer->user);

        $packages = Package::orderBy('name')->get();

        $creditLimitLogs = CustomerCreditLimit::where('user_id', $id)->paginate(10);
        $creditLimit = CustomerCreditLimit::where('user_id', $id)->latest()->first();
        $totalPaymentDue = SchedulePayment::where('user_id', $id)
            ->whereIn('payment_status', ['due', 'late'])
            ->sum('instalment_amount');
        $dueCount = SchedulePayment::where('user_id', $id)->where('payment_status', 'due')->count();
        $lateCount = SchedulePayment::where('user_id', $id)->where('payment_status', 'late')->count();

        $orders = $customer->user->orders()->where('delivery_status', 'delivered')->get();
        $totalOrderAmount = $this->calculateTotalOrderAmount($orders);

        return view('admin.accounts.customer-finance', compact(
            'customer',
            'packages',
            'creditLimitLogs',
            'creditLimit',
            'totalPaymentDue',
            'dueCount',
            'lateCount',
            'totalOrderAmount',
            'data',
            'riskScore'
        ));
    }

    public function upgradePackage(UpgradeCustomerPackageRequest $request, $user)
    {
        $customer = Customer::where('user_id', $user)->firstOrFail();

        $oldPackage = $customer->package->name;

        DB::beginTransaction();

        try {
            $oldData = $customer->toArray();

            $customer->update(['package_id' => $request->package_id]);

            // Log package upgrade with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Package upgraded to meet customer business needs',
                'business_operation',
                []
            );

            $this->auditTrailService->logUpdated(
                $customer,
                $oldData,
                "Upgraded customer package from '{$oldPackage}' to '{$customer->package->name}'",
                $justificationData
            );

            DB::commit();

            return back()->with('success', 'User package upgraded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed upgrade attempt
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'package_upgrade_failed',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'action_summary' => "Failed to upgrade package for customer '{$customer->user->first_name} {$customer->user->last_name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'old_package' => $oldPackage,
                    'new_package_id' => $request->package_id,
                ],
            ]);

            return back()->with('error', 'Failed to upgrade package: '.$e->getMessage());
        }
    }

    public function upgradeLimit(Request $request)
    {
        $data = $request->validate([
            'credit_limit_id' => 'required|exists:customer_credit_limits,id',
            'limit_arabianpay_before' => 'required|numeric',
            'limit_arabianpay_after' => 'required|numeric',
            'comission' => 'nullable|numeric',
        ]);

        DB::beginTransaction();

        try {
            $creditLimit = CustomerCreditLimit::findOrFail($data['credit_limit_id']);
            $oldData = $creditLimit->toArray();

            $creditLimit->update([
                'limit_arabianpay_before' => $data['limit_arabianpay_before'],
                'limit_arabianpay_after' => $data['limit_arabianpay_after'],
                'comission' => $data['comission'] ?? 0,
            ]);

            // Log credit limit update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Credit limit adjusted based on customer performance and risk assessment',
                'risk_management',
                ['credit_limit']
            );

            $this->auditTrailService->logUpdated(
                $creditLimit,
                $oldData,
                "Updated credit limit for customer ID: {$creditLimit->user_id}",
                $justificationData
            );

            DB::commit();

            return back()->with('success', 'Customer credit limit updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'credit_limit_update_failed',
                'entity_type' => 'CustomerCreditLimit',
                'entity_id' => $data['credit_limit_id'] ?? null,
                'action_summary' => 'Failed to update credit limit',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $data,
                ],
            ]);

            return back()->with('error', 'Failed to update credit limit: '.$e->getMessage());
        }
    }

    public function createCreditLimit(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:customers,user_id',
            'package_id' => 'nullable|exists:packages,id',
            'limit_arabianpay_before' => 'required|numeric',
            'limit_arabianpay_after' => 'required|numeric',
            'comission' => 'nullable|numeric',
        ]);

        DB::beginTransaction();

        try {
            $creditLimit = CustomerCreditLimit::create($data);

            // Log credit limit creation with justification
            $justificationData = $this->auditTrailService->withJustification(
                'New credit limit created for customer onboarding',
                'business_operation',
                ['credit_limit', 'commission']
            );

            $this->auditTrailService->logCreated(
                $creditLimit,
                "Created credit limit for customer ID: {$creditLimit->user_id}",
                $justificationData
            );

            DB::commit();

            return back()->with('success', 'Credit limit created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed creation
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'credit_limit_creation_failed',
                'entity_type' => 'CustomerCreditLimit',
                'action_summary' => 'Failed to create credit limit',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'input_data' => $data,
                ],
            ]);

            return back()->with('error', 'Failed to create credit limit: '.$e->getMessage());
        }
    }

    public function updateCustomerStatus(UpdateCustomerStatusRequest $request, $id)
    {
        $status = $request->validated()['status'];

        $customer = Customer::findOrFail($id);
        $oldStatus = $customer->status;

        DB::beginTransaction();

        try {
            $oldData = $customer->toArray();
            $customer->update(['status' => $status]);

            // Log status update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Customer status updated based on compliance review',
                'compliance_obligation',
                []
            );

            $this->auditTrailService->logUpdated(
                $customer,
                $oldData,
                "Updated customer status from '{$oldStatus}' to '{$status}'",
                $justificationData
            );

            // Send email & SMS if approved
            if ($status === 'active') {
                $this->sendEmail(
                    'emails.welcome_account_approved',
                    $customer->user->email,
                    'Account Approved',
                    [
                        'name' => $customer->user->first_name.' '.$customer->user->last_name,
                    ]
                );

                $this->sendSms(
                    $customer->user->phone_number,
                    'Welcome to ArabianPay! Your account has been approved.'
                );

                // Log notification sent
                $this->auditTrailService->log([
                    'event_category' => 'notification_events',
                    'event_type' => 'account_approved_notification',
                    'entity_type' => 'Customer',
                    'entity_id' => $customer->id,
                    'action_summary' => 'Sent approval notifications to customer',
                    'properties' => [
                        'email_sent' => true,
                        'sms_sent' => true,
                        'customer_email' => $customer->user->email,
                        'customer_phone' => $customer->user->phone_number,
                    ],
                ]);
            }

            // Send Firebase Notification
            try {
                $statusMessages = [
                    'approved' => 'Congratulations! Your account has been approved.',
                    'pending' => 'Your account status is now pending. We will notify you once approved.',
                    'suspended' => 'Your account has been suspended. Please contact support for more info.',
                    'blacklisted' => 'Your account has been blacklisted. Please contact support.',
                ];

                $notificationTitle = 'Account Status Updated';
                $notificationBody = $statusMessages[$status] ?? 'Your account status has been updated.';

                $this->firebase->sendCustomNotification(
                    $customer->user_id,
                    $notificationTitle,
                    $notificationBody,
                    [
                        'customer_id' => $customer->id,
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                    ]
                );

                // Log Firebase notification
                $this->auditTrailService->log([
                    'event_category' => 'notification_events',
                    'event_type' => 'firebase_notification_sent',
                    'entity_type' => 'Customer',
                    'entity_id' => $customer->id,
                    'action_summary' => 'Sent Firebase notification for status update',
                    'properties' => [
                        'notification_title' => $notificationTitle,
                        'notification_body' => $notificationBody,
                        'status_change' => $status,
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send customer status notification: '.$e->getMessage(), ['customer_id' => $customer->id]);

                // Log Firebase failure
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'firebase_notification_failed',
                    'entity_type' => 'Customer',
                    'entity_id' => $customer->id,
                    'action_summary' => 'Failed to send Firebase notification for status update',
                    'properties' => [
                        'error_message' => $e->getMessage(),
                        'status_change' => $status,
                    ],
                ]);
            }

            DB::commit();

            return back()->with('success', 'Status updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed status update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'customer_status_update_failed',
                'entity_type' => 'Customer',
                'entity_id' => $customer->id,
                'action_summary' => 'Failed to update customer status',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'old_status' => $oldStatus,
                    'attempted_status' => $status,
                ],
            ]);

            return back()->with('error', 'Failed to update status: '.$e->getMessage());
        }
    }

    public function transactions($id)
    {
        if (! hasSensitivePermission('transaction_references')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_transaction_access',
                'entity_type' => 'Customer',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access customer transactions without permission',
                'properties' => [
                    'permission_required' => 'transaction_references',
                    'customer_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

        // Log view transactions
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_customer_transactions',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed transactions for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['transaction_data'],
            'properties' => [
                'customer_id' => $customer->id,
                'has_permission' => true,
            ],
        ]);

        $transactions = Transaction::with(['order:id,grand_total,shipping_city,general_status', 'user'])
            ->where('user_id', $id)
            ->paginate(10);

        return view('admin.accounts.customer-transactions', compact('customer', 'transactions'));
    }

    public function orders($id)
    {
        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

        // Log view customer orders
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_customer_orders',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed orders for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'properties' => [
                'customer_id' => $customer->id,
                'order_count' => $customer->user->orders()->count(),
            ],
        ]);

        $orders = Order::select([
            'id',
            'user_id',
            'seller_id',
            'payment_type',
            'payment_status',
            'grand_total',
            'coupon_discount',
            'delivery_status',
            'general_status',
            'created_at',
        ])
            ->where('user_id', $id)
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);

        return view('admin.accounts.customer-orders', compact('customer', 'orders'));
    }

    public function payments($id)
    {
        if (! hasSensitivePermission('transaction_references')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_payment_access',
                'entity_type' => 'Customer',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access customer payments without permission',
                'properties' => [
                    'permission_required' => 'transaction_references',
                    'customer_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

        // Log view customer payments
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_customer_payments',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed payment history for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['payment_data'],
            'properties' => [
                'customer_id' => $customer->id,
                'has_permission' => true,
            ],
        ]);

        $wallets = Wallet::select(['id', 'order_id', 'amount', 'balance_after', 'transaction_type', 'status', 'created_at'])
            ->where('user_id', $id)
            ->with('order:id,invoice_number')
            ->latest()
            ->paginate(10);

        return view('admin.accounts.customer-payments', compact('customer', 'wallets'));
    }

    public function customerCompliance($id)
    {
        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

        // Log view customer compliance
        $this->auditTrailService->log([
            'event_category' => 'compliance_operations',
            'event_type' => 'view_customer_compliance',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Viewed compliance information for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'properties' => [
                'customer_id' => $customer->id,
                'customer_status' => $customer->status,
            ],
        ]);

        return view('admin.accounts.customer-compliance', compact('customer'));
    }

    public function customerCreditAssessment($id, CreditAssessmentService $service)
    {
        if (! hasSensitivePermission('credit_decision_output')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_credit_assessment_access',
                'entity_type' => 'Customer',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access credit assessment without permission',
                'properties' => [
                    'permission_required' => 'credit_decision_output',
                    'customer_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        // 1) Fetch merchant (with its user and businessType)
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();

        // Log credit assessment access
        $this->auditTrailService->log([
            'event_category' => 'risk_management',
            'event_type' => 'view_credit_assessment',
            'entity_type' => 'Customer',
            'entity_id' => $customer->id,
            'action_summary' => "Accessed credit assessment for customer '{$customer->user->first_name} {$customer->user->last_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['credit_score', 'financial_data', 'cr_data'],
            'properties' => [
                'customer_id' => $customer->id,
                'has_permission' => true,
                'assessment_type' => 'credit_risk',
            ],
        ]);

        // 2) Fetch all orders for that merchant
        $orders = Order::where('user_id', $customer->id)
            ->orderBy('created_at')
            ->get();

        // 3) Calculate Business Age in years
        $governmentData = is_array($customer->cr_data)
            ? $customer->cr_data
            : json_decode($customer->cr_data, true);

        $issueDateStr = Arr::get($governmentData, 'issueDateGregorian');

        $startDate = $issueDateStr
            ? Carbon::parse($issueDateStr)
            : $customer->created_at;

        $businessAge = $this->formatBusinessAge($startDate);

        // 4) Placeholder credit score calculation

        $creditScore = $service->assess($id);

        // 5) Determine risk level based on score
        if ($creditScore['creditScore']['compositeScore'] >= 80) {
            $riskLevel = 'Low';
        } elseif ($creditScore['creditScore']['compositeScore'] >= 50) {
            $riskLevel = 'Medium';
        } else {
            $riskLevel = 'High';
        }

        // 6) Score components breakdown
        $scoreComponents = [
            'POS Revenue' => $creditScore['creditScore']['monthlyPOSScore'],
            'Industry Risk' => $creditScore['creditScore']['industryRiskScore'],
            'Repayment' => $creditScore['creditScore']['repaymentScore'],
            'Business Age' => $creditScore['creditScore']['businessAgeScore'],
            'Obligations' => $creditScore['creditScore']['obligationsScore'],
            'Liquidity' => $creditScore['creditScore']['liquidityScore'],
            'Supplier Ratings' => $creditScore['creditScore']['supplierScore'],
        ];

        // 7) Payment history timeline
        $monthlyData = Wallet::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereYear('created_at', now()->year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month');

        // Prepare full 12 months even if no data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach (range(1, 12) as $month) {
            $data[] = round($monthlyData[$month] ?? 0, 2);
        }

        $paymentTimeline = [
            'categories' => $months,
            'data' => $data,
        ];

        // 8) Flagged risk factors
        $riskFactors = [
            ['label' => 'Industry Volatility',    'value' => 'High Risk', 'class' => 'text-red-600'],
            ['label' => 'Debt-to-Revenue Ratio',  'value' => '1.2:1',       'class' => 'text-yellow-600'],
            ['label' => 'Recent Disputes',         'value' => '3 Cases',     'class' => 'text-red-600'],
        ];

        // 9) Compliance statuses
        $statusBadgeMap = [
            'approved' => 'badge-success',
            'pending' => 'badge-warning',
            'suspended' => 'badge-neutral',
            'blacklisted' => 'badge-danger',
        ];

        $crValidation = $governmentData['status']['id'] ?? null;
        $crValidationName = $governmentData['status']['name'] ?? 'Unknown';
        $complianceStatus = [
            [
                'name' => 'KYC Verification',
                'status' => ucfirst($customer->status),
                'badge' => 'badge-sm badge-outline '.($statusBadgeMap[$customer->status] ?? 'badge-secondary'),
            ],
            [
                'name' => 'SIMAH Integration',
                'status' => 'Pending',
                'badge' => 'badge-sm badge-outline badge-warning',
            ],
            [
                'name' => 'CR Validation',
                'status' => $crValidationName,
                'badge' => 'badge-sm badge-outline '.($crValidation ? 'badge-success' : 'badge-danger'),
            ],
        ];

        // 10) Return all variables to the Blade
        return view('admin.accounts.customer-credit', compact(
            'customer',
            'orders',
            'businessAge',
            'creditScore',
            'riskLevel',
            'scoreComponents',
            'paymentTimeline',
            'riskFactors',
            'complianceStatus'
        ));
    }

    private function formatBusinessAge(Carbon $start): string
    {
        $interval = $start->diffAsCarbonInterval(Carbon::now());

        if ($interval->y > 0) {
            $decimal = round($interval->y + ($interval->m / 12), 1);

            return $decimal.' Year';
        }

        if ($interval->m > 0) {
            return $interval->m.' Month';
        }

        return $interval->d.' Days';
    }

    private function calculateBase(Order $order): float
    {
        $items = map_product_details($order->product_details);
        $subTotal = $items->sum('total');
        $shipping = $order->shipping_cost ?? 0;
        $discount = $order->coupon_discount ?? 0;
        $tax = calculate_order_tax($order);

        return $subTotal + $tax + $shipping - $discount;
    }

    private function calculateCreditScore($orders, $customer = null)
    {
        // Same calculation logic as before...
        $monthlyRevenue = Wallet::where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $monthlyPOSScore = min($monthlyRevenue / 50000, 1) * 25;

        $businessAge = 0;
        if ($customer) {
            $issueDateStr = Arr::get(is_array($customer->goverment_data) ? $customer->goverment_data : json_decode($customer->goverment_data, true), 'issueDateGregorian');
            $startDate = $issueDateStr ? Carbon::parse($issueDateStr) : $customer->created_at;
            $interval = $startDate->diffAsCarbonInterval(Carbon::now());
            $businessAge = $interval->y + ($interval->m / 12) + ($interval->d / 365);
        }

        if ($businessAge >= 3) {
            $businessAgeScore = 10;
        } elseif ($businessAge >= 1) {
            $businessAgeScore = 7;
        } else {
            $businessAgeScore = 5;
        }
        $businessAgeScore *= 1;

        $industryRisk = $customer && isset($customer->businessType->risk_level) ? $customer->businessType->risk_level : 'Medium';
        $industryRiskScores = ['low' => 15, 'medium' => 10, 'high' => 5];
        $industryRiskScore = $industryRiskScores[$industryRisk] ?? 10;

        $totalPurchases = Order::where('user_id', $customer->user_id)->where('delivery_status', 'delivered')
            ->get()
            ->reduce(function ($carry, $order) {
                return $carry + $this->calculateBase($order);
            }, 0.0);
        $totalPayments = Wallet::where('transaction_type', 'user_repayment')->sum('amount');

        $existingDebt = $totalPurchases - $totalPayments;
        if ($existingDebt > 0) {
            $obligationsScore = 10 - min($monthlyRevenue / $existingDebt, 10);
        } else {
            $obligationsScore = 10;
        }

        $repaymentDelays = Transaction::where('user_id', $customer->user_id)->count('payment_status');
        if ($repaymentDelays == 0) {
            $repaymentScore = 20;
        } elseif ($repaymentDelays <= 2) {
            $repaymentScore = 15;
        } else {
            $repaymentScore = 5;
        }

        $liquidityTrend = 'positive';
        $liquidityScores = ['positive' => 10, 'flat' => 5, 'negative' => 0];
        $liquidityScore = $liquidityScores[$liquidityTrend] ?? 5;

        $supplierRating = Product::where('user_id', $customer->user_id)->sum('rating');
        $supplierScore = $supplierRating * 2;

        $compositeScore = $monthlyPOSScore
            + $businessAgeScore
            + $industryRiskScore
            + $obligationsScore
            + $repaymentScore
            + $liquidityScore
            + $supplierScore;

        return [
            'monthlyRevenue' => round($monthlyRevenue, 2),
            'monthlyPOSScore' => round($monthlyPOSScore, 2),
            'businessAge' => round($businessAge, 2),
            'businessAgeScore' => round($businessAgeScore, 2),
            'industryRisk' => ucfirst($industryRisk),
            'industryRiskScore' => round($industryRiskScore, 2),
            'existingDebt' => round($existingDebt, 2),
            'obligationsScore' => round($obligationsScore, 2),
            'repaymentDelays' => $repaymentDelays,
            'repaymentScore' => round($repaymentScore, 2),
            'liquidityTrend' => $liquidityTrend,
            'liquidityScore' => round($liquidityScore, 2),
            'supplierRating' => round($supplierRating, 2),
            'supplierScore' => round($supplierScore, 2),
            'compositeScore' => round($compositeScore, 2),
        ];
    }

    public function nafath()
    {
        // Log view Nafath verifications
        $this->auditTrailService->logViewOperation(
            'view_nafath_verifications',
            'NafathVerification',
            'Viewed Nafath identity verification records',
            [
                'page' => request()->get('page', 1),
                'per_page' => 10,
            ]
        );

        $nafathRecords = NafathVerification::orderBy('id', 'desc')->paginate(10);

        $phoneNumbers = $nafathRecords->pluck('phone_number')->filter()->unique()->toArray();

        // Fetch users one by one using whereEncrypted
        $users = collect();
        foreach ($phoneNumbers as $phone) {
            $user = User::whereEncrypted('phone_number', $phone)->first();
            if ($user) {
                $users[$phone] = $user;
            }
        }

        // Get emails from users
        $emails = $users->pluck('email')->unique()->toArray();

        // Get latest CrValidation for each email using whereEncrypted
        $crValidations = collect();
        foreach ($emails as $email) {
            $latestCr = CrValidation::whereEncrypted('email', $email)
                ->orderByDesc('id')
                ->first();

            if ($latestCr) {
                $crValidations[$email] = $latestCr;
            }
        }

        // Attach cr_data to each Nafath record based on user email
        $nafathRecords->getCollection()->transform(function ($item) use ($users, $crValidations) {
            $phone = $item->phone_number;

            $user = isset($users[$phone]) ? $users[$phone] : null;
            $email = $user ? $user->email : null;

            $item->cr_data = $email && isset($crValidations[$email]) ? $crValidations[$email]->cr_data : null;

            return $item;
        });

        return view('admin.accounts.nafath', ['nafath' => $nafathRecords]);
    }

    private function calculateTotalOrderAmountWithoutTax($orders): float
    {
        $total = 0;

        foreach ($orders as $order) {
            $items = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax = calculate_order_tax($order);

            $base = $subTotal + $tax + $shipping - $discount;
        }

        return $base;
    }

    private function calculateTotalOrderAmount($orders): float
    {
        $total = 0;

        foreach ($orders as $order) {
            $items = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax = calculate_order_tax($order);

            $base = $subTotal + $tax + $shipping - $discount;

            $commissionPct = get_system_commission();
            $commissionAmount = $base * ($commissionPct / 100);

            $commissionTaxPct = get_commission_tax();
            $commissionTaxAmt = $commissionAmount * ($commissionTaxPct / 100);

            $total += $base + $commissionAmount + $commissionTaxAmt;
        }

        return $total;
    }
}
