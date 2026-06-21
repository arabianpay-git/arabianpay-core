<?php

namespace App\Http\Controllers;

use App\Exports\SuppliersExport;
use App\Models\Approval;
use App\Models\BusinessCategory;
use App\Models\CustomerCreditLimit;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShopSetting;
use App\Models\SupplierBank;
use App\Models\SupplierPayout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Rules\NoHtml;
use App\Services\AuditTrailService;
use App\Services\FirebaseService;
use App\Services\OdooService;
use App\Services\WathqService;
use App\Traits\EmailSender;
use App\Traits\SmsSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    use EmailSender, SmsSender;

    protected $wathqService;

    protected $firebase;

    protected $auditTrailService;

    protected $odooService;

    public function __construct(WathqService $wathqService, FirebaseService $firebase, AuditTrailService $auditTrailService, OdooService $odooService)
    {
        $this->wathqService = $wathqService;
        $this->firebase = $firebase;
        $this->auditTrailService = $auditTrailService;
        $this->odooService = $odooService;
    }

    public function suppliers(Request $request)
    {
        $user = currentUser();
        $search = $request->input('search');
        $status = $request->input('status');
        $employee = $request->input('employee');
        $onboardingStep = $request->input('onboarding_step');

        // Log view suppliers list
        $this->auditTrailService->logViewOperation(
            'view_list',
            'Supplier',
            'Viewed suppliers list',
            [
                'page' => $request->get('page', 1),
                'per_page' => 10,
                'search_query' => $search,
                'status_filter' => $status,
                'employee_filter' => $employee,
                'onboarding_step_filter' => $onboardingStep,
                'user_type' => $user->user_type,
                'is_manager' => $user->is_manager ?? false,
            ]
        );

        $merchants = $this->merchantsQueryFromRequest($request)->get();

        // Apply search filter
        if ($search) {
            // Log search operation
            $this->auditTrailService->logSearch(
                'Supplier',
                $search,
                $merchants->count(),
                [
                    'properties' => [
                        'search_type' => 'manual_filter',
                        'status_filter' => $status,
                        'employee_filter' => $employee,
                        'onboarding_step' => $onboardingStep,
                    ],
                ]
            );

            $merchants = $this->filterMerchants($merchants, $search);
        }

        // Manual pagination
        $page = $request->input('page', 1);
        $perPage = 10;

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $merchants->forPage($page, $perPage),
            $merchants->count(),
            $perPage,
            $page,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return view('admin.accounts.partials.suppliers-table', [
                'merchants' => $paginated,
            ])->render();
        }

        return view('admin.accounts.suppliers', [
            'merchants' => $paginated,
        ]);
    }

    /**
     * Export suppliers list to Excel (same filters as the list: search, status, employee, visibility rules).
     */
    public function exportSuppliers(Request $request)
    {
        $user = currentUser();
        $search = $request->input('search');
        $status = $request->input('status');
        $employee = $request->input('employee');

        $this->auditTrailService->logViewOperation(
            'export_list',
            'Supplier',
            'Exported suppliers list to Excel',
            [
                'search_query' => $search,
                'status_filter' => $status,
                'employee_filter' => $employee,
                'user_type' => $user->user_type,
                'is_manager' => $user->is_manager ?? false,
            ]
        );

        $merchants = $this->merchantsQueryFromRequest($request)->get();

        if ($search) {
            $merchants = $this->filterMerchants($merchants, $search);
        }

        $filename = 'suppliers-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new SuppliersExport($merchants), $filename);
    }

    /**
     * Base query for suppliers index and export (shared filters and authorization).
     */
    private function merchantsQueryFromRequest(Request $request)
    {
        $user = currentUser();
        $status = $request->input('status');
        $employee = $request->input('employee');
        $integration = $request->input('integration');

        return Merchant::with(['user', 'businessType', 'assigned', 'approval'])
            ->select('id', 'user_id', 'business_type_id', 'cr_number', 'status', 'is_integration', 'assigned_to', 'created_at')
            ->when(
                ! ($user->user_type === 'employee' && $user->is_manager) && $user->user_type !== 'admin',
                fn ($query) => $query->where('assigned_to', $user->id)
            )
            ->when(! $status, fn ($q) => $q->where('status', '!=', 'blacklisted'))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($employee, fn ($q) => $q->where('assigned_to', $employee))
            ->when($integration !== null && $integration !== '', fn ($q) => $q->where('is_integration', (bool) $integration))
            ->orderByDesc('id')
            ->orderByRaw('ISNULL(assigned_to) DESC');
    }

    public function updateCommission(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'commission' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $approval = Approval::firstOrNew(['user_id' => $request->user_id]);
            $oldCommission = $approval->commission;

            $approval->commission = $request->commission;
            $approval->save();

            // Log commission update
            $this->auditTrailService->log([
                'event_category' => 'financial_operations',
                'event_type' => 'update_supplier_commission',
                'entity_type' => 'Approval',
                'entity_id' => $approval->id,
                'action_summary' => "Updated commission for supplier ID: {$request->user_id} from {$oldCommission} to {$request->commission}",
                'properties' => [
                    'user_id' => $request->user_id,
                    'old_commission' => $oldCommission,
                    'new_commission' => $request->commission,
                ],
            ]);

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed commission update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'commission_update_failed',
                'entity_type' => 'Approval',
                'action_summary' => "Failed to update commission for supplier ID: {$request->user_id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'user_id' => $request->user_id,
                    'commission_value' => $request->commission,
                ],
            ]);

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function toggleIntegration(Request $request, $id)
    {
        $merchant = Merchant::findOrFail($id);
        $merchant->is_integration = ! $merchant->is_integration;
        $merchant->save();

        return response()->json([
            'success' => true,
            'is_integration' => $merchant->is_integration,
        ]);
    }

    public function supplierShop($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();

        // Log view supplier shop
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_supplier_shop',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed shop settings for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
            ],
        ]);

        $supplierShop = ShopSetting::where('user_id', $id)->first();

        return view('admin.accounts.supplier-shop', compact('merchant', 'supplierShop'));
    }

    public function supplierShopSubmit(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new NoHtml],
            'logo' => 'nullable|string',
            'sliders' => 'nullable|array',
            'sliders.*' => 'nullable|string',
            'banner' => 'nullable|string',
            'phone_number' => ['required', 'regex:/^(?:\+9665\d{8}|05\d{8})$/', 'string'],
            'address' => ['required', 'string', 'max:255', new NoHtml],
        ]);

        DB::beginTransaction();

        try {
            $shopSetting = ShopSetting::updateOrCreate(
                ['user_id' => $request->user_id],
                $validated
            );

            // Log shop settings update
            $action = $shopSetting->wasRecentlyCreated ? 'create' : 'update';

            if ($action === 'create') {
                $this->auditTrailService->logCreated(
                    $shopSetting,
                    "Created shop settings for supplier ID: {$request->user_id}"
                );
            } else {
                $this->auditTrailService->logUpdated(
                    $shopSetting,
                    $shopSetting->getOriginal(),
                    "Updated shop settings for supplier ID: {$request->user_id}"
                );
            }

            DB::commit();

            return redirect()->back()->with('success', 'Shop settings saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed shop settings update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'shop_settings_update_failed',
                'entity_type' => 'ShopSetting',
                'action_summary' => "Failed to update shop settings for supplier ID: {$request->user_id}",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'user_id' => $request->user_id,
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to save shop settings: '.$e->getMessage());
        }
    }

    public function supplierProducts($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();

        // Log view supplier products
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_supplier_products',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed products for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
            ],
        ]);

        $products = Product::where('user_id', $id)
            ->with(['category:id,name', 'brand:id,name'])
            ->select(['id', 'name', 'thumbnail', 'unit_price', 'brand_id', 'current_stock', 'approved', 'published', 'created_at'])
            ->latest()
            ->paginate(10);

        return view('admin.accounts.suppliers-products', compact('merchant', 'products'));
    }

    public function supplierProfile($id)
    {
        $user = currentUser();

        $riskScore = get_risk_score($id);

        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType', 'riskManagement', 'approval')
            ->when(
                ! (
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (! $merchant) {
            // Log failed access attempt
            $this->auditTrailService->log([
                'event_category' => 'access_control',
                'event_type' => 'supplier_access_denied',
                'entity_type' => 'Supplier',
                'action_summary' => 'Attempted to access supplier profile without proper assignment or permissions',
                'properties' => [
                    'attempted_supplier_id' => $id,
                    'current_user_id' => $user->id,
                    'user_type' => $user->user_type,
                    'is_manager' => $user->is_manager ?? false,
                ],
            ]);

            return redirect()->route('suppliers')->with('error', __('Supplier not found or not assigned to you.'));
        }

        // Log view supplier profile
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_supplier_profile',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed profile for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
                'cr_number' => $merchant->cr_number,
                'status' => $merchant->status,
            ],
        ]);

        $businessCategory = [];
        if ($merchant->business_category_id) {
            $businessCategory = BusinessCategory::whereIn(
                'id',
                json_decode($merchant->business_category_id, true)
            )->get();
        }

        // Calculate supplier performance metrics
        $supplierUserId = $merchant->user_id;

        // Total Products
        $totalProducts = Product::where('user_id', $supplierUserId)->count();

        // Total Orders
        $totalOrders = Order::where('seller_id', $supplierUserId)->count();

        // Total Revenue (from completed orders)
        $totalRevenue = Order::where('seller_id', $supplierUserId)
            ->where('general_status', 'accepted')
            ->where('delivery_status', 'delivered')
            ->get()
            ->sum(function ($order) {
                return (float) $order->grand_total;
            });

        // Average Order Value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();

        // Fetch all relevant orders for the seller
        $orders = Order::where('seller_id', $supplierUserId)
            ->where('general_status', 'accepted')
            ->where('delivery_status', 'delivered')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get();

        // Group orders by month
        $monthlyRevenueData = $orders
            ->groupBy(function ($order) {
                return $order->created_at->format('M Y');
            })
            ->map(function ($monthOrders, $month) {
                $revenue = $monthOrders->sum(function ($order) {
                    return (float) $order->grand_total;
                });

                return [
                    'month' => $month,
                    'revenue' => $revenue,
                    'orders_count' => $monthOrders->count(),
                ];
            })
            ->sortKeys()
            ->values();

        $monthLabels = [];
        $monthlyRevenue = [];
        $monthlyOrders = [];

        foreach ($monthlyRevenueData as $data) {
            $monthLabels[] = $data['month'];
            $monthlyRevenue[] = (float) $data['revenue'];
            $monthlyOrders[] = (int) $data['orders_count'];
        }

        // If no recent data, create sample data for the last 6 months
        if (empty($monthlyRevenue)) {
            $monthLabels = [];
            for ($i = 5; $i >= 0; $i--) {
                $monthLabels[] = now()->subMonths($i)->format('M Y');
            }
            $monthlyRevenue = [0, 0, 0, 0, 0, 0];
            $monthlyOrders = [0, 0, 0, 0, 0, 0];
        }

        // Wallet Balance
        $walletBalance = Wallet::where('seller_id', $supplierUserId)->sum('balance_after');

        // Pending Payouts
        $pendingPayouts = SupplierPayout::where('supplier_id', $supplierUserId)
            ->where('status', 'pending')
            ->sum('amount');

        // Completed Payouts
        $completedPayouts = SupplierPayout::where('supplier_id', $supplierUserId)
            ->where('status', 'completed')
            ->sum('amount');

        // Order Status Distribution
        $orderStatuses = Order::where('seller_id', $supplierUserId)
            ->selectRaw('delivery_status, COUNT(*) as count')
            ->groupBy('delivery_status')
            ->pluck('count', 'delivery_status')
            ->toArray();

        $completedOrders = $orderStatuses['delivered'] ?? 0;
        $pendingOrders = $orderStatuses['pending'] ?? 0;
        $cancelledOrders = $orderStatuses['cancelled'] ?? 0;

        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        // Recent Orders for the Recent Activity component
        $recentOrders = Order::where('seller_id', $supplierUserId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        if (empty($merchant->goverment_data) && $merchant->cr_number) {
            $wathqData = $this->wathqService->fetchCrData($merchant->cr_number);

            if ($wathqData) {
                $merchant->goverment_data = $wathqData;
                $merchant->save();

                // Log CR data fetch
                $this->auditTrailService->log([
                    'event_category' => 'data_sync',
                    'event_type' => 'supplier_cr_data_fetch',
                    'entity_type' => 'Supplier',
                    'entity_id' => $merchant->id,
                    'action_summary' => "Fetched CR data from Wathq for supplier '{$merchant->user->business_name}'",
                    'properties' => [
                        'cr_number' => $merchant->cr_number,
                        'data_source' => 'wathq',
                        'success' => true,
                    ],
                ]);
            }
        }

        $mainUserId = $merchant->user->main_user_id ?: $merchant->user_id;

        $relatedUserIds = \App\Models\User::where(function ($q) use ($mainUserId) {
            $q->where('id', $mainUserId)->orWhere('main_user_id', $mainUserId);
        })->pluck('id');

        $supplierBanks = SupplierBank::whereIn('user_id', $relatedUserIds)
            ->with('user')
            ->get();

        return view('admin.accounts.supplier-profile', array_merge([
            'merchant' => $merchant,
            'businessCategory' => $businessCategory,
            'supplierBanks' => $supplierBanks,
            'riskScore' => $riskScore,
            // Performance metrics
            'totalProducts' => $totalProducts,
            'totalOrders' => $totalOrders,
            'totalRevenue' => $totalRevenue,
            'avgOrderValue' => $avgOrderValue,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyOrders' => $monthlyOrders,
            'monthLabels' => $monthLabels,
            'walletBalance' => $walletBalance,
            'pendingPayouts' => $pendingPayouts,
            'completedPayouts' => $completedPayouts,
            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'cancelledOrders' => $cancelledOrders,
            'completionRate' => $completionRate,
            // Recent orders for the Recent Activity component
            'recentOrders' => $recentOrders,
        ]));
    }

    public function supplierFinance($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();

        // Log view supplier finance
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_supplier_finance',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed finance details for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
                'cr_number' => $merchant->cr_number,
            ],
        ]);

        $creditLimitLogs = CustomerCreditLimit::where('user_id', $id)->paginate(10);

        // Total orders supplied
        $totalOrders = Order::where('seller_id', $id)->count();

        // Total products supplied
        $totalProducts = Order::where('seller_id', $id)
            ->get()
            ->reduce(function ($carry, $order) {
                $items = json_decode($order->product_details, true) ?: [];
                foreach ($items as $item) {
                    $carry += $item['quantity'] ?? 0;
                }

                return $carry;
            }, 0);

        // Average delivery time
        $avgDeliveryTime = Order::where('seller_id', $id)
            ->where('delivery_status', 'delivered')
            ->get()
            ->map(function ($order) {
                return \Carbon\Carbon::parse($order->created_at)
                    ->diffInDays(\Carbon\Carbon::parse($order->updated_at));
            })->average();

        // Total returned orders
        $totalReturns = Order::where('seller_id', $id)
            ->where('delivery_status', 'returned')
            ->count();

        // Total cancelled orders
        $totalCancelled = Order::where('seller_id', $id)
            ->where('general_status', 'cancelled')
            ->count();

        // Last supplied order date
        $lastOrderDate = Order::where('seller_id', $id)
            ->latest('created_at')
            ->value('created_at');

        $totalPaid = Wallet::where('seller_id', $id)
            ->where('transaction_type', 'seller_payment')
            ->sum('amount');

        // Fetch all transactions to summarize
        $transactions = Transaction::with('order')
            ->where('seller_id', $id)
            ->get();

        $totalEntitlement = 0;
        $pendingPayment = 0;

        foreach ($transactions as $tx) {
            $order = $tx->order;
            if (! $order) {
                continue;
            }

            $items = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax = calculate_order_tax($order);

            $base = $subTotal + $shipping + $tax - $discount;
            $totalEntitlement += $base;

            $supplierPaid = Wallet::where('seller_id', $id)
                ->where('order_id', $order->id)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');

            $supplierDue = $base - $supplierPaid;
            $pendingPayment += $supplierDue;
        }

        // Commission percentage
        $totalCommissionPercentage = get_seller_commission($merchant->user_id);
        $totalCommission = round(($totalEntitlement * $totalCommissionPercentage) / 100, 2);

        $stockCount = Product::where('user_id', $merchant->user_id)
            ->sum('current_stock');

        $avgRating = Product::where('user_id', $merchant->user_id)
            ->avg('rating');

        $status = ucfirst($merchant->status);

        $lastPaymentDate = Wallet::where('seller_id', $id)->where('transaction_type', 'seller_payment')
            ->latest('updated_at')
            ->value('updated_at');

        return view('admin.accounts.supplier-finance', compact(
            'merchant',
            'creditLimitLogs',
            'totalOrders',
            'totalProducts',
            'avgDeliveryTime',
            'totalReturns',
            'totalCancelled',
            'lastOrderDate',
            'totalEntitlement',
            'totalPaid',
            'stockCount',
            'avgRating',
            'status',
            'pendingPayment',
            'lastPaymentDate',
            'totalCommission'
        ));
    }

    public function supplierCompliance($id)
    {
        $merchant = Merchant::where('user_id', $id)->with('user')->firstOrFail();

        // Log view supplier compliance
        $this->auditTrailService->log([
            'event_category' => 'compliance_operations',
            'event_type' => 'view_supplier_compliance',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed compliance information for supplier '{$merchant->user->business_name}'",
            'pdpl_category' => 'legal_obligation',
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
                'supplier_status' => $merchant->status,
            ],
        ]);

        $contract = Approval::where('user_id', $id)->select('contract', 'contract_end_date', 'created_at')->first();
        $supplierBank = SupplierBank::where('user_id', $id)->select('iban_certificate')->first();

        return view('admin.accounts.supplier-compliance', compact('merchant', 'contract', 'supplierBank'));
    }

    public function updateSupplierStatusApprove(Request $request, $id)
    {
        $merchant = Merchant::where('user_id', $id)->firstOrFail();

        $request->validate([
            'commission' => 'required|numeric|min:0|max:100',
            'reason' => 'nullable|string|max:1000',
            'contract' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'payment_schedule' => 'required|integer|min:0|max:100',
            'contract_end_date' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $contractPath = null;
            if ($request->hasFile('contract')) {
                $path = $request->file('contract')->store('contracts', 'public');
                $contractPath = Storage::url($path);
            }

            $approvalData = [
                'user_id' => $id,
                'employee_id' => Auth::id(),
                'commission' => $request->commission,
                'reason' => $request->reason,
                'contract' => $contractPath,
                'payment_schedule' => $request->payment_schedule,
                'contract_end_date' => $request->contract_end_date,
            ];

            if ($request->has('fahman_score')) {
                $approvalData['fahman_score'] = $request->fahman_score;
            }

            $approval = Approval::create($approvalData);

            $oldStatus = $merchant->status;
            $merchant->status = $request->status ?? 'approved';
            $merchant->save();

            // Log approval creation
            $justificationData = $this->auditTrailService->withJustification(
                'Supplier approval with contract and commission terms',
                'contractual_obligation',
                ['commission', 'contract', 'payment_schedule']
            );

            $this->auditTrailService->logCreated(
                $approval,
                "Created approval for supplier '{$merchant->user->business_name}' with {$request->commission}% commission",
                $justificationData
            );

            // Log status update
            $this->auditTrailService->logUpdated(
                $merchant,
                ['status' => $oldStatus],
                "Updated supplier status from '{$oldStatus}' to '{$merchant->status}' after approval",
                $justificationData
            );

            // Send email & SMS notifications
            $this->sendEmail(
                'emails.welcome_account_approved',
                $merchant->user->email,
                'Account Approved',
                [
                    'name' => $merchant->user->first_name.' '.$merchant->user->last_name,
                ]
            );

            $this->sendSms(
                $merchant->user->phone_number,
                'Welcome to ArabianPay! Your account has been approved.'
            );

            // Log notifications
            $this->auditTrailService->log([
                'event_category' => 'notification_events',
                'event_type' => 'supplier_approval_notifications',
                'entity_type' => 'Supplier',
                'entity_id' => $merchant->id,
                'action_summary' => 'Sent approval notifications to supplier',
                'properties' => [
                    'email_sent' => true,
                    'sms_sent' => true,
                    'supplier_email' => $merchant->user->email,
                    'supplier_phone' => $merchant->user->phone_number,
                    'commission_rate' => $request->commission,
                ],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Approval submitted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed approval
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'supplier_approval_failed',
                'entity_type' => 'Supplier',
                'entity_id' => $merchant->id,
                'action_summary' => "Failed to approve supplier '{$merchant->user->business_name}'",
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'commission' => $request->commission ?? null,
                    'contract_uploaded' => $request->hasFile('contract'),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to submit approval: '.$e->getMessage());
        }
    }

    public function updateSupplierStatus(Request $request, $id)
    {
        $status = $request->validate([
            'status' => 'required|in:under_review,active,contract_sent,approved,suspended,pending,blacklisted',
        ])['status'];

        $merchant = Merchant::where('user_id', $id)->firstOrFail();
        $oldStatus = $merchant->status;

        DB::beginTransaction();

        try {
            $oldData = $merchant->toArray();
            $merchant->status = $status;
            $merchant->save();

            // Log status update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Supplier status updated as part of routine review',
                'business_operation',
                []
            );

            $this->auditTrailService->logUpdated(
                $merchant,
                $oldData,
                "Updated supplier status from '{$oldStatus}' to '{$status}'",
                $justificationData
            );

            // Send Firebase notification
            try {
                $description = "Supplier '{$merchant->user->business_name}' status updated from {$oldStatus} to {$status}";

                $this->firebase->sendCustomNotification(
                    Auth::user()->id,
                    'Supplier Status Update',
                    $description,
                    [
                        'click_action' => route('suppliers'),
                    ]
                );

                // Log Firebase notification
                $this->auditTrailService->log([
                    'event_category' => 'notification_events',
                    'event_type' => 'firebase_supplier_status_update',
                    'entity_type' => 'Supplier',
                    'entity_id' => $merchant->id,
                    'action_summary' => 'Sent Firebase notification for supplier status update',
                    'properties' => [
                        'notification_title' => 'Supplier Status Update',
                        'notification_body' => $description,
                        'status_change' => "{$oldStatus} to {$status}",
                    ],
                ]);
            } catch (\Throwable $e) {
                Log::error('Failed to send supplier status notification: '.$e->getMessage(), ['supplier_id' => $merchant->id]);
            }

            // Sync with Odoo when status changes to contract_sent
            if ($status === 'contract_sent' && is_null($merchant->user->odoo_customer_id)) {
                try {
                    $vendorId = $this->odooService->createVendor($merchant);
                    $merchant->user->odoo_customer_id = $vendorId;
                    $merchant->user->save();

                    $this->auditTrailService->log([
                        'event_category' => 'integration_events',
                        'event_type' => 'odoo_vendor_created',
                        'entity_type' => 'Supplier',
                        'entity_id' => $merchant->id,
                        'action_summary' => "Created vendor in Odoo for supplier '{$merchant->user->business_name}'",
                        'properties' => [
                            'odoo_vendor_id' => $vendorId,
                        ],
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Odoo vendor creation failed: '.$e->getMessage(), [
                        'merchant_id' => $merchant->id,
                    ]);
                }
            }

            DB::commit();

            return back()->with('success', 'Status updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            // Log failed status update
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'supplier_status_update_failed',
                'entity_type' => 'Supplier',
                'entity_id' => $merchant->id,
                'action_summary' => 'Failed to update supplier status',
                'properties' => [
                    'error_message' => $e->getMessage(),
                    'old_status' => $oldStatus,
                    'attempted_status' => $status,
                ],
            ]);

            return back()->with('error', 'Failed to update status: '.$e->getMessage());
        }
    }

    public function supplierTransactions($id)
    {
        if (! hasSensitivePermission('transaction_references')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_supplier_transaction_access',
                'entity_type' => 'Supplier',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access supplier transactions without permission',
                'properties' => [
                    'permission_required' => 'transaction_references',
                    'supplier_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

        // Log view supplier transactions
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_supplier_transactions',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed transactions for supplier '{$merchant->user->business_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['transaction_data'],
            'properties' => [
                'supplier_id' => $merchant->id,
                'has_permission' => true,
            ],
        ]);

        $transactions = Transaction::with(['order:id,grand_total,shipping_city,general_status', 'user'])
            ->where('seller_id', $id)
            ->paginate(10);

        return view('admin.accounts.supplier-transactions', compact('merchant', 'transactions'));
    }

    public function supplierOrders($id)
    {
        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

        // Log view supplier orders
        $this->auditTrailService->log([
            'event_category' => 'view_operations',
            'event_type' => 'view_supplier_orders',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed orders for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
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
            ->where('seller_id', $id)
            ->with(['user', 'pickupPoint'])
            ->paginate(10);

        return view('admin.accounts.supplier-orders', compact('merchant', 'orders'));
    }

    public function supplierPayments($id)
    {
        if (! hasSensitivePermission('transaction_references')) {
            // Log unauthorized access attempt
            $this->auditTrailService->log([
                'event_category' => 'security_events',
                'event_type' => 'unauthorized_supplier_payment_access',
                'entity_type' => 'Supplier',
                'entity_id' => $id,
                'action_summary' => 'Attempted to access supplier payments without permission',
                'properties' => [
                    'permission_required' => 'transaction_references',
                    'supplier_id' => $id,
                ],
            ]);

            return back()->with('error', translate('Access Restricted'));
        }

        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

        // Log view supplier payments
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_supplier_payments',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed payment history for supplier '{$merchant->user->business_name}'",
            'pdpl_category' => 'legal_obligation',
            'pii_fields_involved' => ['payment_data'],
            'properties' => [
                'supplier_id' => $merchant->id,
                'has_permission' => true,
            ],
        ]);

        $paginator = Wallet::where('transaction_type', 'seller_payment')
            ->where('seller_id', $id)
            ->with(['seller.merchant', 'order'])
            ->latest()
            ->paginate(10);

        $summary = $paginator->getCollection()->map(fn ($wallet) => [
            'seller_name' => trim($wallet->seller->first_name.' '.$wallet->seller->last_name),
            'seller_business' => $wallet->seller->business_name,
            'invoice_number' => strtoupper($wallet->order->invoice_number ?? 'N/A'),
            'payment_date' => $wallet->updated_at->format(dateFormat()),
            'payment_invoice' => $this->calculateTotalOrderAmountWithoutTax(collect([$wallet->order])),
            'tax_number' => optional($wallet->seller->merchant)->vat_register_number ?? 'N/A',
            'amount_paid' => $wallet->balance_after,
            'tax_total' => calculate_order_tax($wallet->order),
            'total_bills' => $this->calculateTotalOrderAmount(collect([$wallet->order])),
        ]);

        return view('admin.accounts.supplier-payments', compact('merchant', 'summary', 'paginator'));
    }

    public function supplierSales($id)
    {
        $merchant = Merchant::with('user', 'businessType')
            ->where('user_id', $id)
            ->firstOrFail();

        // Log view supplier sales
        $this->auditTrailService->log([
            'event_category' => 'financial_operations',
            'event_type' => 'view_supplier_sales',
            'entity_type' => 'Supplier',
            'entity_id' => $merchant->id,
            'action_summary' => "Viewed sales report for supplier '{$merchant->user->business_name}'",
            'properties' => [
                'supplier_id' => $merchant->id,
                'business_name' => $merchant->user->business_name,
            ],
        ]);

        $orders = Order::where('seller_id', $id)->latest()->paginate(10);

        $orders->getCollection()->transform(function ($order) {
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

            $totalAmount = $base + $commissionAmount + $commissionTaxAmt;

            $supplierDue = Wallet::where('seller_id', $order->seller_id)
                ->where('order_id', $order->id)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');

            $totalSuplierDue = $base - $supplierDue;

            // Add calculated fields to order
            $order->calculated = [
                'subTotal' => $subTotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'tax' => $tax,
                'base' => $base,
                'commissionPct' => $commissionPct,
                'commissionAmount' => $commissionAmount,
                'commissionTaxPct' => $commissionTaxPct,
                'commissionTaxAmt' => $commissionTaxAmt,
                'totalAmount' => $totalAmount,
                'supplierDue' => $supplierDue,
                'totalSuplierDue' => $totalSuplierDue,
            ];

            return $order;
        });

        return view('admin.accounts.supplier-sales', compact('merchant', 'orders'));
    }

    /**
     * Display trashed suppliers
     */
    public function trashed(Request $request)
    {
        $user = currentUser();

        if (! ($user->user_type === 'admin' || ($user->user_type === 'employee' && $user->is_manager))) {
            abort(403, 'Unauthorized action.');
        }

        $search = $request->input('search');
        $status = $request->input('status');
        $deletedBy = $request->input('deleted_by');
        $deletedDate = $request->input('deleted_date');

        // Build query for trashed merchants
        $merchantsQuery = Merchant::onlyTrashed()
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }, 'businessType', 'assigned'])
            ->select('id', 'user_id', 'business_type_id', 'cr_number', 'status', 'assigned_to', 'deleted_at');

        // Apply filters
        if ($status) {
            $merchantsQuery->where('status', $status);
        }

        if ($search) {
            $merchantsQuery->whereHas('user', function ($query) use ($search) {
                $query->withTrashed()
                    ->where('business_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        if ($deletedBy) {
            // This would require additional logic to filter by who deleted
            // For now, we'll skip this filter or implement if you have audit trail data
        }

        if ($deletedDate) {
            $today = now();
            switch ($deletedDate) {
                case 'today':
                    $merchantsQuery->whereDate('deleted_at', $today);
                    break;
                case 'yesterday':
                    $merchantsQuery->whereDate('deleted_at', $today->subDay());
                    break;
                case 'week':
                    $merchantsQuery->where('deleted_at', '>=', $today->subWeek());
                    break;
                case 'month':
                    $merchantsQuery->where('deleted_at', '>=', $today->subMonth());
                    break;
                case 'older':
                    $merchantsQuery->where('deleted_at', '<', $today->subMonth());
                    break;
            }
        }

        $merchants = $merchantsQuery->latest('deleted_at')->paginate(20);

        // Get users who have deleted merchants (for filter dropdown)
        $deletedByUsers = User::whereIn('id', function ($query) {
            $query->select('actor_id')
                ->from('audit_trails')
                ->where('event_type', 'soft_delete')
                ->where('entity_type', 'Merchant');
        })->get();

        // Log view trashed suppliers
        $this->auditTrailService->logViewOperation(
            'view_trashed',
            'Merchant',
            'Viewed trashed suppliers list',
            [
                'total_trashed' => $merchants->total(),
                'current_page' => $merchants->currentPage(),
                'search_query' => $search,
                'status_filter' => $status,
                'viewed_by' => $user->id,
            ]
        );

        return view('admin.accounts.suppliers-trashed', compact('merchants', 'deletedByUsers'));
    }

    /**
     * Soft delete a merchant
     */
    public function softDelete(Request $request, $id)
    {
        $user = currentUser();

        if (! ($user->user_type === 'admin' || ($user->user_type === 'employee' && $user->is_manager))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $merchant = Merchant::findOrFail($id);

            // Check if merchant is already soft deleted
            if ($merchant->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant is already in trash.',
                ], 400);
            }

            // Get merchant data before deletion for audit trail
            $merchantData = $merchant->toArray();
            $businessName = $merchant->user->business_name ?? 'N/A';
            $userData = $merchant->user ? $merchant->user->toArray() : null;

            // Soft delete the merchant
            $merchant->delete();

            // Log the action
            $this->auditTrailService->logCrudOperation(
                'soft_delete',
                'Merchant',
                $merchant->id,
                "Soft deleted merchant ID: {$merchant->id} ({$businessName})",
                array_merge(
                    ['merchant' => $merchantData, 'user' => $userData],
                    ['assigned_to' => $merchant->assigned_to]
                ),
                null,
                $this->auditTrailService->withJustification(
                    'Merchant soft deleted and moved to trash for potential restoration',
                    'legitimate_interest',
                    ['business_name', 'email', 'phone_number', 'cr_number']
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Merchant has been moved to trash successfully.',
                'redirect' => $request->has('redirect_to_trash') ? route('merchants.trashed') : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Soft delete failed: '.$e->getMessage(), [
                'merchant_id' => $id,
                'user_id' => $user->id,
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_logs',
                'event_type' => 'soft_delete_failed',
                'entity_type' => 'Merchant',
                'entity_id' => $id,
                'action_summary' => "Failed to soft delete merchant ID: {$id}",
                'properties' => [
                    'error' => $e->getMessage(),
                    'attempted_by' => $user->id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete merchant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permanently delete a merchant (force delete)
     */
    public function forceDelete(Request $request, $id)
    {
        $user = currentUser();

        if ($user->user_type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        DB::beginTransaction();

        try {
            // Find the merchant including trashed ones
            $merchant = Merchant::withTrashed()->findOrFail($id);

            // Get merchant data before deletion for audit trail
            $merchantData = $merchant->toArray();
            $businessName = $merchant->user->business_name ?? 'N/A';

            // Get the user before deleting merchant
            $userModel = $merchant->user;
            $userData = $userModel ? $userModel->toArray() : null;

            // Log the action before deletion
            $this->auditTrailService->logCrudOperation(
                'force_delete',
                'Merchant',
                $merchant->id,
                "Permanently deleting merchant ID: {$merchant->id} ({$businessName})",
                array_merge(
                    ['merchant' => $merchantData, 'user' => $userData],
                    [
                        'assigned_to' => $merchant->assigned_to,
                        'business_type' => $merchant->businessType->name ?? 'N/A',
                        'status' => $merchant->status,
                    ]
                ),
                null,
                array_merge(
                    $this->auditTrailService->withJustification(
                        'Merchant permanently deleted with all related data - no recovery possible',
                        'legitimate_interest',
                        ['cr_number', 'vat_register_number', 'owner_name', 'owner_iqama_number']
                    ),
                    [
                        'performed_by' => $user->id,
                        'performed_by_email' => $user->email,
                        'deletion_timestamp' => now()->toDateTimeString(),
                    ]
                )
            );

            // Permanently delete the associated user and all related data first
            if ($userModel) {
                $this->deleteUserRelatedData($userModel);

                // Log user deletion
                $this->auditTrailService->logCrudOperation(
                    'force_delete',
                    'User',
                    $userModel->id,
                    "Permanently deleted user ID: {$userModel->id} ({$userModel->email}) as part of merchant force delete",
                    ['user' => $userData, 'merchant_id' => $id],
                    null,
                    $this->auditTrailService->withJustification(
                        'User permanently deleted along with merchant due to complete account removal',
                        'legitimate_interest',
                        ['email', 'phone_number', 'first_name', 'last_name']
                    )
                );

                // Permanently delete the user
                $userModel->forceDelete();
            }

            // Permanently delete the merchant
            $merchant->forceDelete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Merchant and associated user have been permanently deleted.',
                'redirect' => $request->has('redirect_to_trash') ? route('merchants.trashed') : null,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Force delete failed: '.$e->getMessage(), [
                'merchant_id' => $id,
                'user_id' => $user->id,
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_logs',
                'event_type' => 'force_delete_failed',
                'entity_type' => 'Merchant',
                'entity_id' => $id,
                'action_summary' => "Failed to force delete merchant ID: {$id}",
                'properties' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'attempted_by' => $user->id,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to permanently delete: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restore a soft deleted merchant
     */
    public function restore(Request $request, $id)
    {
        $user = currentUser();

        if (! ($user->user_type === 'admin' || ($user->user_type === 'employee' && $user->is_manager))) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $merchant = Merchant::onlyTrashed()->findOrFail($id);

            // Get merchant data before restoration
            $merchantData = $merchant->toArray();
            $businessName = $merchant->user->business_name ?? 'N/A';

            // Restore the merchant
            $merchant->restore();

            // Optionally restore the user if needed
            $userRestored = false;
            if ($merchant->user && $merchant->user->trashed()) {
                $merchant->user->restore();
                $userRestored = true;
            }

            // Log the restoration action
            $this->auditTrailService->logCrudOperation(
                'restore',
                'Merchant',
                $merchant->id,
                "Restored merchant ID: {$merchant->id} ({$businessName}) from trash".
                    ($userRestored ? ' (user also restored)' : ''),
                null,
                array_merge(
                    ['merchant' => $merchant->fresh()->toArray()],
                    [
                        'user_restored' => $userRestored,
                        'restored_at' => now()->toDateTimeString(),
                    ]
                ),
                $this->auditTrailService->withJustification(
                    'Merchant restored from trash as requested by authorized user',
                    'legitimate_interest',
                    ['business_name', 'cr_number']
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Merchant has been restored successfully.',
                'redirect' => $request->has('redirect_to_suppliers') ? route('suppliers') : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Restore failed: '.$e->getMessage(), [
                'merchant_id' => $id,
                'user_id' => $user->id,
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_logs',
                'event_type' => 'restore_failed',
                'entity_type' => 'Merchant',
                'entity_id' => $id,
                'action_summary' => "Failed to restore merchant ID: {$id} from trash",
                'properties' => [
                    'error' => $e->getMessage(),
                    'attempted_by' => $user->id,
                    'timestamp' => now()->toDateTimeString(),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to restore merchant: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Empty entire trash (admin only)
     */
    public function emptyTrash(Request $request)
    {
        $user = currentUser();

        if ($user->user_type !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Validate confirmation text
        $request->validate([
            'confirmation' => 'required|in:DELETE ALL',
        ]);

        DB::beginTransaction();

        try {
            $trashedMerchants = Merchant::onlyTrashed()->get();
            $totalCount = $trashedMerchants->count();

            if ($totalCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trash is already empty.',
                ]);
            }

            $deletedIds = [];
            $deletedBusinessNames = [];

            foreach ($trashedMerchants as $merchant) {
                $deletedIds[] = $merchant->id;
                $deletedBusinessNames[] = $merchant->user->business_name ?? 'Unknown';

                // Delete related user data if exists
                if ($merchant->user) {
                    $this->deleteUserRelatedData($merchant->user);
                    $merchant->user->forceDelete();
                }

                $merchant->forceDelete();
            }

            // Log bulk deletion
            $this->auditTrailService->log([
                'event_category' => 'system_operations',
                'event_type' => 'empty_trash',
                'entity_type' => 'Merchant',
                'action_summary' => "Emptied trash - permanently deleted {$totalCount} merchants",
                'properties' => [
                    'deleted_count' => $totalCount,
                    'deleted_ids' => $deletedIds,
                    'deleted_business_names' => $deletedBusinessNames,
                    'performed_by' => $user->id,
                    'timestamp' => now()->toDateTimeString(),
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully permanently deleted {$totalCount} merchants from trash.",
                'count' => $totalCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Empty trash failed: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to empty trash: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper function to delete all user related data
     */
    private function deleteUserRelatedData(User $user)
    {
        try {
            // Delete all related records
            $user->merchant()->forceDelete();
            $user->products()->delete();
            $user->coupons()->delete();
            $user->pickupPoint()->delete();
            $user->orders()->delete();
            $user->sellerOrders()->delete();
            $user->transactions()->delete();
            $user->sales()->delete();
            $user->supportTickets()->delete();
            $user->SchedulePayments()->delete();
            $user->userSchedulePayment()->delete();
            $user->sellerSchedulePayment()->delete();
            $user->paymentsMade()->delete();
            $user->paymentsReceived()->delete();
            $user->productWishlists()->delete();
            $user->sellerWishlists()->delete();
            $user->userRefund()->delete();
            $user->sellerRefund()->delete();
            $user->userWallet()->delete();
            $user->sellerWallet()->delete();
            $user->customer()->delete();
            $user->transferRequestsSent()->delete();
            $user->transferRequestsReceived()->delete();
            $user->deviceTokens()->delete();
            $user->notifications()->delete();
            $user->supplierBanks()->delete();
            $user->nafathVerification()->delete();

            // If user has customer credit limit
            if ($user->customerCreditLimit) {
                $user->customerCreditLimit()->delete();
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete user related data: '.$e->getMessage(), [
                'user_id' => $user->id,
            ]);
            throw $e;
        }
    }

    /**
     * Private function to filter merchants collection based on search input
     */
    private function filterMerchants($merchants, $search)
    {
        $search = strtolower(trim($search));
        $searchTerms = explode(' ', $search);

        return $merchants->filter(function ($merchant) use ($search, $searchTerms) {
            $user = $merchant->user;

            // Check full email, business_name, and phone_number
            if (
                str_contains(strtolower($user->email), $search) ||
                str_contains(strtolower($user->business_name), $search) ||
                str_contains(strtolower($user->phone_number), $search)
            ) {
                return true;
            }

            // Check first_name and last_name for each search term
            foreach ($searchTerms as $term) {
                if (
                    str_contains(strtolower($user->first_name), $term) ||
                    str_contains(strtolower($user->last_name), $term)
                ) {
                    return true;
                }
            }

            // Also check phone number for each search term (in case of partial phone number search)
            foreach ($searchTerms as $term) {
                if (str_contains(strtolower($user->phone_number), $term)) {
                    return true;
                }
            }

            return false;
        });
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

    public function fetchWathiq(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            // allow leaving cr_number empty (will fallback to merchant->cr_number), otherwise require 7+ digits
            'cr_number' => ['nullable', 'regex:/^\d{7,}$/'],
        ], [
            'cr_number.regex' => 'CR number must contain only digits and be at least 7 characters long.',
        ]);

        $userId = (int) $validated['user_id'];
        $merchant = Merchant::where('user_id', $userId)->first();

        if (! $merchant) {
            return response()->json([
                'success' => false,
                'message' => 'Merchant not found for provided user_id.',
            ], 404);
        }

        $crNumber = $request->input('cr_number') ?? $merchant->cr_number;

        if (empty($crNumber) || ! preg_match('/^\d{7,}$/', $crNumber)) {
            return response()->json([
                'success' => false,
                'message' => 'CR number is required (either enter it now or store it on the merchant). Must be at least 7 digits.',
            ], 422);
        }

        $wathqBase = rtrim(env('WATHQ_API_BASE', 'https://api.wathq.sa/'), '/').'/';
        $apiKey = env('WATHQ_API_KEY', 'nxNtcpyb0cqiLfkj8umAdkhqJGA8x4Az'); // keep secret in .env

        try {
            $url = sprintf('%scommercial-registration/fullinfo/%s', $wathqBase, $crNumber);

            $response = Http::withHeaders([
                'apiKey' => $apiKey,
                'Accept' => 'application/json',
            ])->get($url, [
                'language' => 'en',
            ]);

            // If HTTP returned non-2xx
            if (! $response->successful()) {
                Log::error("Wathq API returned non-success for CR {$crNumber}. Status: {$response->status()}. Body: {$response->body()}");
                // Try to surface API message if present
                $body = $response->json();
                $message = $body['message'] ?? 'Wathq API returned an error. Please try again later.';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'raw' => $body,
                ], 502);
            }

            // Parse JSON to check for known Wathq error codes like "400.1.5"
            $resJson = $response->json();

            if (isset($resJson['code'])) {
                // Special error from Wathq — forward message back
                Log::warning("Wathq API returned code {$resJson['code']} for CR {$crNumber}: ".($resJson['message'] ?? ''));

                return response()->json([
                    'success' => false,
                    'message' => $resJson['message'] ?? 'Wathq returned an error.',
                    'code' => $resJson['code'],
                    'raw' => $resJson,
                ], 422);
            }

            // Store the complete JSON string into goverment_data as requested
            $merchant->goverment_data = $response->body();
            $merchant->save();

            return response()->json([
                'success' => true,
                'message' => 'Wathiq data updated successfully.',
                'data' => $resJson, // optional: parsed JSON
            ]);
        } catch (\Exception $e) {
            Log::error("Wathq API request failed for CR {$crNumber}: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => 'We could not verify the Commercial Registration at the moment. Please try again shortly or contact support.',
            ], 500);
        }
    }
}
