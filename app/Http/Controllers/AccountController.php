<?php

namespace App\Http\Controllers;

use App\Models\{Approval, BusinessCategory, CrValidation, Customer, CustomerCreditLimit, Merchant, NafathVerification, Order, Package, Payment, Product, SchedulePayment, ShopSetting, SupplierBank, Transaction, User, Wallet};
use App\Rules\NoHtml;
use App\Services\CreditAssessmentService;
use App\Services\FirebaseService;
use App\Services\RiskAnalyticsService;
use App\Services\WathqService;
use App\Traits\EmailSender;
use App\Traits\SmsSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AccountController extends Controller
{
    use SmsSender, EmailSender;

    protected $wathqService;
    protected $firebase;

    public function __construct(WathqService $wathqService, FirebaseService $firebase)
    {
        $this->wathqService = $wathqService;
        $this->firebase = $firebase;
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

    private function calculateTotalOrderAmountWithoutTax($orders): float
    {
        $total = 0;

        foreach ($orders as $order) {
            $items    = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax      = calculate_order_tax($order);

            $base = $subTotal + $tax + $shipping - $discount;
        }

        return $base;
    }

    public function customers()
    {
        $user = currentUser();

        $customers = Customer::with([
            'assigned',
            'user',
            'package',
            'user.orders' => function ($q) {
                $q->where('delivery_status', 'delivered');
            }
        ])
            ->select(['id', 'assigned_to', 'user_id', 'package_id', 'cr_number', 'address', 'purchasing_volume', 'status', 'created_at'])
            ->when(
                !(
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

        $logs = Activity::where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('admin.accounts.customer-log', compact('customer', 'logs'));
    }

    public function customerBusiness()
    {
        dd('Remaning');
    }

    public function customerSimah()
    {
        dd('Remaning');
    }

    public function customerProfile($id, CreditAssessmentService $creditService, RiskAnalyticsService $riskService)
    {
        $user = currentUser();

        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->when(
                !(
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (!$customer) {
            return redirect()->route('customers')->with('error', __('Customer not found or not assigned to you.'));
        }

        $data = $creditService->assess($id);
        $riskScore = $riskService->calculateForUser($customer->user);


        if (empty($customer->cr_data) && $customer->cr_number) {
            $wathqData = $this->wathqService->fetchCrData($customer->cr_number);

            if ($wathqData) {
                $customer->cr_data = $wathqData;
                $customer->save();
            }
        }

        return view('admin.accounts.customer-profile', compact('customer', 'data', 'riskScore'));
    }


    public function customerFinance($id, CreditAssessmentService $creditService, RiskAnalyticsService $riskService)
    {
        $customer = Customer::with('user', 'package')
            ->where('user_id', $id)
            ->firstOrFail();
        $data = $creditService->assess($id);
        $riskScore = $riskService->calculateForUser($customer->user);

        $packages = Package::orderBy('name')->get();

        $creditLimitLogs    = CustomerCreditLimit::where('user_id', $id)->paginate(10);
        $creditLimit        = CustomerCreditLimit::where('user_id', $id)->latest()->first();
        $totalPaymentDue    = SchedulePayment::where('user_id', $id)
            ->whereIn('payment_status', ['due', 'late'])
            ->sum('instalment_amount');
        $dueCount           = SchedulePayment::where('user_id', $id)->where('payment_status', 'due')->count();
        $lateCount          = SchedulePayment::where('user_id', $id)->where('payment_status', 'late')->count();

        $orders             = $customer->user->orders()->where('delivery_status', 'delivered')->get();
        $totalOrderAmount   = $this->calculateTotalOrderAmount($orders);

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

    public function upgradePackage(Request $request, $user)
    {
        $request->validate(['package_id' => 'required|exists:packages,id']);
        $customer = Customer::where('user_id', $user)->firstOrFail();

        $oldPackage = $customer->package->name;

        $customer->update(['package_id' => $request->package_id]);

        // Log the activity
        $batchUuid = (string) Str::uuid();

        $customer->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " upgrade Customer: {$customer->user->first_name} {$customer->user->last_name} [$customer->id] package from $oldPackage to {$customer->package->name}",
            properties: [
                'old_status' => $oldPackage,
                'new_status' => $customer->package->name,
                'reason' => $reason ?? null,
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid,
            ],
        );

        return back()->with('success', 'User package upgraded successfully.');
    }

    public function upgradeLimit(Request $request)
    {
        $data = $request->validate([
            'credit_limit_id'            => 'required|exists:customer_credit_limits,id',
            'limit_arabianpay_before'    => 'required|numeric',
            'limit_arabianpay_after'     => 'required|numeric',
            'comission'                  => 'nullable|numeric',
        ]);

        CustomerCreditLimit::findOrFail($data['credit_limit_id'])
            ->update([
                'limit_arabianpay_before' => $data['limit_arabianpay_before'],
                'limit_arabianpay_after'  => $data['limit_arabianpay_after'],
                'comission'               => $data['comission'] ?? 0,
            ]);

        return back()->with('success', 'Customer credit limit updated successfully.');
    }

    public function createCreditLimit(Request $request)
    {
        $data = $request->validate([
            'user_id'                    => 'required|exists:customers,user_id',
            'package_id'                 => 'nullable|exists:packages,id',
            'limit_arabianpay_before'    => 'required|numeric',
            'limit_arabianpay_after'     => 'required|numeric',
            'comission'                  => 'nullable|numeric',
        ]);

        CustomerCreditLimit::create($data);

        return back()->with('success', 'Credit limit created successfully.');
    }

    public function updateCustomerStatus(Request $request, $id)
    {
        $status = $request->validate([
            'status' => 'required|in:approved,suspended,pending,blacklisted',
        ])['status'];

        $customer = Customer::findOrFail($id);
        $oldStatus = $customer->status;
        $customer->update(['status' => $status]);

        // Log the activity
        $batchUuid = (string) Str::uuid();

        $customer->logModelAction(
            event: 'update',
            description: Auth::user()->first_name . " " . Auth::user()->last_name . " update Customer: {$customer->user->first_name} {$customer->user->last_name} [$customer->id] status from $oldStatus to {$customer->status}",
            properties: [
                'old_status' => $oldStatus,
                'new_status' => $customer->status,
                'reason' => $reason ?? null, // reson can be optional
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid, // Add batch UUID for consistency
            ],
        );

        if ($status === 'approved') {

            $this->sendEmail(
                'emails.welcome_account_approved',
                $customer->user->email,
                'Account Approved',
                [
                    'name' => $customer->user->first_name . " " . $customer->user->last_name,
                ]
            );


            $this->sendSms(
                $customer->user->phone_number,
                'Welcome to ArabianPay! Your account has been approved.'
            );
        }

        return back()->with('success', 'Status updated successfully!');
    }


    protected $selectFields = [
        'id',
        'uuid',
        'refrence_payment',
        'user_id',
        'seller_id',
        'order_id',
        'loan_amount',
        'loan_start_date',
        'loan_end_date',
        'payment_status',
        'settlement_status',
        'created_at',
    ];

    public function transactions($id)
    {
        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

        $transactions = Transaction::select($this->selectFields)
            ->with(['order:id,grand_total,shipping_city,general_status', 'user'])
            ->where('user_id', $id)
            ->paginate(10);

        return view('admin.accounts.customer-transactions', compact('customer', 'transactions'));
    }

    public function orders($id)
    {
        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

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
            'created_at'
        ])
            ->where('user_id', $id)
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);

        return view('admin.accounts.customer-orders', compact('customer', 'orders'));
    }

    public function payments($id)
    {
        $customer = Customer::where('user_id', $id)->with('user')->firstOrFail();

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
        return view('admin.accounts.customer-compliance', compact('customer'));
    }

    // ---- Supplier Methods (similarly optimized) ----

    public function suppliers()
    {
        $user = currentUser();

        $merchants = Merchant::with('user', 'businessType', 'assigned')
            ->select('id', 'user_id', 'business_type_id', 'cr_number', 'status', 'assigned_to', 'created_at')
            ->when(
                !(
                    $user->user_type === 'employee' && $user->is_manager
                ) && $user->user_type !== 'admin',
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->orderByDesc('id')
            ->orderByRaw('ISNULL(assigned_to) DESC')
            ->paginate(10);

        return view('admin.accounts.suppliers', compact('merchants'));
    }

    public function supplierShop($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();
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

        $shopSetting = ShopSetting::updateOrCreate(
            ['user_id' => $request->user_id],
            $validated
        );

        return redirect()->back()->with('success', 'Shop settings saved successfully!');
    }

    public function supplierProducts($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();

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

        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->when(
                !(
                    ($user->user_type === 'employee' && $user->is_manager) || $user->user_type === 'admin'
                ),
                function ($query) use ($user) {
                    $query->where('assigned_to', $user->id);
                }
            )
            ->first();

        if (!$merchant) {
            return redirect()->route('suppliers')->with('error', __('Supplier not found or not assigned to you.'));
        }

        $sellerShop = ShopSetting::where('user_id', $merchant->user_id)
            ->select('address')
            ->first();

        $businessCategory = [];
        if ($merchant->business_category_id) {
            $businessCategory = BusinessCategory::whereIn(
                'id',
                json_decode($merchant->business_category_id, true)
            )->get();
        }

        $stats = [
            'totalProducts' => Product::where('user_id', $merchant->user_id)->count(),
            'totalOrders'   => Order::where('seller_id', $merchant->user_id)->count(),
            'revenue'       => Payment::where('seller_id', $merchant->user_id)->sum('amount'),
            'walletBalance' => Wallet::where('seller_id', $merchant->user_id)->sum('balance_after'),
        ];

        if (empty($merchant->goverment_data) && $merchant->cr_number) {

            $wathqData = $this->wathqService->fetchCrData($merchant->cr_number);

            if ($wathqData) {
                $merchant->goverment_data = $wathqData;
                $merchant->save();
            }
        }

        $supplierBank = SupplierBank::where('user_id', $merchant->user_id)->first();

        return view('admin.accounts.supplier-profile', array_merge([
            'merchant'         => $merchant,
            'businessCategory' => $businessCategory,
            'sellerShop'       => $sellerShop,
            'supplierBank'     => $supplierBank,
        ], $stats));
    }

    public function supplierFinance($id)
    {
        $merchant = Merchant::where('user_id', $id)
            ->with('user', 'businessType')
            ->firstOrFail();

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

        // Average delivery time (days between created_at and updated_at for delivered orders)
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
            if (! $order) continue;

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
            'contract_end_date' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        DB::transaction(function () use ($request, $id) {
            $contractPath = null;
            if ($request->hasFile('contract')) {
                $path = $request->file('contract')->store('contracts', 'public');
                $contractPath = Storage::url($path);
            }

            Approval::create([
                'user_id' => $id,
                'employee_id' => Auth::id(),
                'commission' => $request->commission,
                'reason' => $request->reason,
                'contract' => $contractPath,
                'fahman_score' => $request->fahman_score,
                'payment_schedule' => $request->payment_schedule,
                'contract_end_date' => $request->contract_end_date,
            ]);

            $merchant = Merchant::where('user_id', $id)->firstOrFail();

            $oldStatus = $merchant->status;
            $merchant->status = $request->status ?? 'approved';
            $merchant->save();

            // Send email & SMS notifications
            $this->sendEmail(
                'emails.welcome_account_approved',
                $merchant->user->email,
                'Account Approved',
                [
                    'name' => $merchant->user->first_name . " " . $merchant->user->last_name,
                ]
            );

            $this->sendSms(
                $merchant->user->phone_number,
                'Welcome to ArabianPay! Your account has been approved.'
            );

            // Log the activity with batch UUID
            $batchUuid = (string) Str::uuid();

            $merchant->logModelAction(
                event: 'update',
                description: Auth::user()->first_name . " " . Auth::user()->last_name . " updated Supplier: {$merchant->user->first_name} {$merchant->user->last_name} [$merchant->id] status from $oldStatus to {$merchant->status}",
                properties: [
                    'old_status' => $oldStatus,
                    'new_status' => $merchant->status,
                    'reason' => $request->reason ?? null,
                    'ip' => request()->ip(),
                    'batch_uuid' => $batchUuid,
                ],
            );
        });

        return redirect()->back()->with('success', 'Approval submitted successfully.');
    }

    public function updateSupplierStatus(Request $request, $id)
    {
        $status = $request->validate([
            'status' => 'required|in:under_review,active,contract_sent,approved,suspended,pending,blacklisted',
        ])['status'];

        $merchant = Merchant::where('user_id', $id)->firstOrFail();
        $oldStatus = $merchant->status;
        $merchant->status = $status;
        $merchant->save();

        $merchant->update(['status' => $status]);

        $description = Auth::user()->first_name . " " . Auth::user()->last_name . " update Supplier: {$merchant->user->first_name} {$merchant->user->last_name} [$merchant->id] status from $oldStatus to {$merchant->status}";
        // Log the activity
        $batchUuid = (string) Str::uuid();

        $merchant->logModelAction(
            event: 'update',
            description: $description,
            properties: [
                'old_status' => $oldStatus,
                'new_status' => $merchant->status,
                'reason' => $reason ?? null,
                'ip' => request()->ip(),
                'batch_uuid' => $batchUuid,
            ],
        );

        $this->firebase->sendCustomNotification(
            Auth::user()->id,
            'Customer Status Update',
            $description,
            [
                'click_action' => route('suppliers'),
            ]
        );

        return back()->with('success', 'Status updated successfully!');
    }

    public function supplierTransactions($id)
    {
        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

        $transactions = Transaction::select($this->selectFields)
            ->with(['order:id,grand_total,shipping_city,general_status', 'user'])
            ->where('seller_id', $id)
            ->paginate(10);

        return view('admin.accounts.supplier-transactions', compact('merchant', 'transactions'));
    }

    public function supplierOrders($id)
    {
        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

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
            'created_at'
        ])
            ->where('seller_id', $id)
            ->with(['user', 'pickupPoint'])
            ->paginate(10);

        return view('admin.accounts.supplier-orders', compact('merchant', 'orders'));
    }

    public function supplierPayments($id)
    {
        $merchant = Merchant::where('user_id', $id)->with('user', 'businessType')->firstOrFail();

        $paginator = Wallet::where('transaction_type', 'seller_payment')
            ->where('seller_id', $id)
            ->with(['seller.merchant', 'order'])
            ->latest()
            ->paginate(10);

        // CHANGED: reuse calculateTotalOrderAmount for each wallet's order
        $summary = $paginator->getCollection()->map(fn($wallet) => [
            'seller_name'     => trim($wallet->seller->first_name . ' ' . $wallet->seller->last_name),
            'seller_business' => $wallet->seller->business_name,
            'invoice_number'  => strtoupper($wallet->order->invoice_number ?? 'N/A'),
            'payment_date'    => $wallet->updated_at->format('Y-m-d'),
            'payment_invoice' => $this->calculateTotalOrderAmountWithoutTax(collect([$wallet->order])),
            'tax_number'      => optional($wallet->seller->merchant)->vat_register_number ?? 'N/A',
            'amount_paid'     => $wallet->balance_after,
            'tax_total'       => calculate_order_tax($wallet->order),
            'total_bills'     => $this->calculateTotalOrderAmount(collect([$wallet->order])),
        ]);

        return view('admin.accounts.supplier-payments', compact('merchant', 'summary', 'paginator'));
    }

    public function supplierSales($id)
    {
        $merchant = Merchant::with('user', 'businessType')
            ->where('user_id', $id)
            ->firstOrFail();

        $orders = Order::where('seller_id', $id)->latest()->paginate(10);

        $orders->getCollection()->transform(function ($order) {
            $items         = map_product_details($order->product_details);
            $subTotal      = $items->sum('total');
            $shipping      = $order->shipping_cost ?? 0;
            $discount      = $order->coupon_discount ?? 0;
            $tax           = calculate_order_tax($order);

            $base          = $subTotal + $tax + $shipping - $discount;

            $commissionPct     = get_system_commission();
            $commissionAmount  = $base * ($commissionPct / 100);

            $commissionTaxPct  = get_commission_tax();
            $commissionTaxAmt  = $commissionAmount * ($commissionTaxPct / 100);

            $totalAmount       = $base + $commissionAmount + $commissionTaxAmt;

            $supplierDue = Wallet::where('seller_id', $order->seller_id)
                ->where('order_id', $order->id)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');

            $totalSuplierDue = $base - $supplierDue;

            // Add calculated fields to order
            $order->calculated = [
                'subTotal'         => $subTotal,
                'shipping'         => $shipping,
                'discount'         => $discount,
                'tax'              => $tax,
                'base'             => $base,
                'commissionPct'    => $commissionPct,
                'commissionAmount' => $commissionAmount,
                'commissionTaxPct' => $commissionTaxPct,
                'commissionTaxAmt' => $commissionTaxAmt,
                'totalAmount'      => $totalAmount,
                'supplierDue'      => $supplierDue,
                'totalSuplierDue'  => $totalSuplierDue,
            ];

            return $order;
        });

        return view('admin.accounts.supplier-sales', compact('merchant', 'orders'));
    }

    public function customerCreditAssessment($id, CreditAssessmentService $service)
    {
        // 1) Fetch merchant (with its user and businessType)
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();

        // 2) Fetch all orders for that merchant
        $orders = Order::where('user_id', $customer->id)
            ->orderBy('created_at')
            ->get();

        // 3) Calculate Business Age in years
        $governmentData = is_array($customer->cr_data)
            ? $customer->cr_data
            : json_decode($customer->cr_data, true);

        $issueDateStr = Arr::get($governmentData, 'issueDateGregorian');

        $startDate    = $issueDateStr
            ? Carbon::parse($issueDateStr)
            : $customer->created_at;

        $businessAge  = $this->formatBusinessAge($startDate);

        // 4) Placeholder credit score calculation

        $creditScore = $service->assess($id);

        // 5) Determine risk level based on score
        //    TODO: Adjust thresholds to your requirements
        if ($creditScore['creditScore']['compositeScore'] >= 80) {
            $riskLevel = 'Low';
        } elseif ($creditScore['creditScore']['compositeScore'] >= 50) {
            $riskLevel = 'Medium';
        } else {
            $riskLevel = 'High';
        }

        // 6) Score components breakdown (labels => percentages)
        //    TODO: Build this array from your scoring logic
        $scoreComponents = [
            'POS Revenue'       => $creditScore['creditScore']['monthlyPOSScore'],
            'Industry Risk'     => $creditScore['creditScore']['industryRiskScore'],
            'Repayment'         => $creditScore['creditScore']['repaymentScore'],
            'Business Age'      => $creditScore['creditScore']['businessAgeScore'],
            'Obligations'       => $creditScore['creditScore']['obligationsScore'],
            'Liquidity'         => $creditScore['creditScore']['liquidityScore'],
            'Supplier Ratings'  => $creditScore['creditScore']['supplierScore'],
        ];

        // 7) Payment history timeline (e.g., payments per month)
        //    TODO: Build real data series and categories
        $monthlyData = Wallet::selectRaw("MONTH(created_at) as month, SUM(amount) as total")
            ->where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereYear('created_at', now()->year)
            ->groupByRaw("MONTH(created_at)")
            ->pluck('total', 'month');

        // Prepare full 12 months even if no data
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach (range(1, 12) as $month) {
            $data[] = round($monthlyData[$month] ?? 0, 2);
        }

        $paymentTimeline = [
            'categories' => $months,
            'data'       => $data,
        ];


        // 8) Flagged risk factors
        //    Each item: ['label' => '', 'value' => '', 'class' => 'text-red-600' etc.]
        //    TODO: Generate from real risk checks
        $riskFactors = [
            ['label' => 'Industry Volatility',    'value' => 'High Risk', 'class' => 'text-red-600'],
            ['label' => 'Debt-to-Revenue Ratio',  'value' => '1.2:1',       'class' => 'text-yellow-600'],
            ['label' => 'Recent Disputes',         'value' => '3 Cases',     'class' => 'text-red-600'],
        ];

        // 9) Compliance statuses
        //    Each item: ['name' => '', 'status' => '', 'badge' => 'badge-success' etc.]
        //    TODO: Fetch real flags from your KYC/SIMAH/CR services
        $statusBadgeMap = [
            'approved'     => 'badge-success',
            'pending'      => 'badge-warning',
            'suspended'    => 'badge-neutral',
            'blacklisted'  => 'badge-danger',
        ];

        $crValidation = $governmentData['status']['id'] ?? null;
        $crValidationName = $governmentData['status']['name'] ?? 'Unknown';
        $complianceStatus = [
            [
                'name'   => 'KYC Verification',
                'status' => ucfirst($customer->status),
                'badge'  => 'badge-sm badge-outline ' . ($statusBadgeMap[$customer->status] ?? 'badge-secondary')
            ],
            [
                'name'   => 'SIMAH Integration',
                'status' => 'Pending',
                'badge'  => 'badge-sm badge-outline badge-warning'
            ],
            [
                'name'   => 'CR Validation',
                'status' => $crValidationName,
                'badge'  => 'badge-sm badge-outline ' . ($crValidation ? 'badge-success' : 'badge-danger')
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
            return $decimal . ' Year';
        }

        if ($interval->m > 0) {
            return $interval->m . ' Month';
        }

        return $interval->d . ' Days';
    }

    private function calculateBase(Order $order): float
    {
        $items    = map_product_details($order->product_details);
        $subTotal = $items->sum('total');
        $shipping = $order->shipping_cost   ?? 0;
        $discount = $order->coupon_discount ?? 0;
        $tax      = calculate_order_tax($order);

        return $subTotal + $tax + $shipping - $discount;
    }

    private function calculateCreditScore($orders, $customer = null)
    {
        // dd($customer->user_id);
        // 1) Monthly POS Revenue (weight 25%)
        // Sum total revenue from orders, assume 'total_amount' column or similar
        $monthlyRevenue = Wallet::where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Normalize monthlyRevenue by 50,000 as per formula in PDF
        $monthlyPOSScore = min($monthlyRevenue / 50000, 1) * 25;

        // 2) Business Age & Stability (weight 10%)
        // Use businessAge string from formatBusinessAge - convert to years approx
        // Assume businessAge format like '2.4 Year', '3 Month', or '20 Days'
        $businessAge = 0;
        if ($customer) {
            $issueDateStr = Arr::get(is_array($customer->goverment_data) ? $customer->goverment_data : json_decode($customer->goverment_data, true), 'issueDateGregorian');
            $startDate    = $issueDateStr ? Carbon::parse($issueDateStr) : $customer->created_at;
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
        $businessAgeScore *= 1; // 10% weight = max 10 points, so already correct.

        // 3) Industry/Market Risk (weight 15%)
        // Placeholder: Assume merchant has a riskLevel attribute or default to Medium

        $industryRisk = $customer && isset($customer->businessType->risk_level) ? $customer->businessType->risk_level : 'Medium';
        $industryRiskScores = ['low' => 15, 'medium' => 10, 'high' => 5];
        $industryRiskScore = $industryRiskScores[$industryRisk] ?? 10;

        // 4) Existing Financial Obligations (weight 10%)
        // Placeholder: Assume $existingDebt in local variable, for now set to 0 (no debt)
        $totalPurchases   = Order::where('user_id', $customer->user_id)->where('delivery_status', 'delivered')
            ->get()
            ->reduce(function ($carry, $order) {
                return $carry + $this->calculateBase($order);
            }, 0.0);
        $totalPayments    = Wallet::where('transaction_type', 'user_repayment')->sum('amount');

        $existingDebt = $totalPurchases - $totalPayments;
        // Formula: 10 - min(Monthly Revenue / Existing Debt, 10), if debt=0, max score 10
        if ($existingDebt > 0) {
            $obligationsScore = 10 - min($monthlyRevenue / $existingDebt, 10);
        } else {
            $obligationsScore = 10;
        }

        // 5) Repayment Behavior (weight 20%)
        // Placeholder: Assume repaymentDelays count from SIMAH or history, default 1 delay
        $repaymentDelays = Transaction::where('user_id', $customer->user_id)->count('payment_status');
        if ($repaymentDelays == 0) {
            $repaymentScore = 20;
        } elseif ($repaymentDelays <= 2) {
            $repaymentScore = 15;
        } else {
            $repaymentScore = 5;
        }


        // 6) Bank Balance & Liquidity Trend (weight 10%)
        // Placeholder: Assume positive trend, flat, or negative
        $liquidityTrend = 'positive'; // options: positive, flat, negative
        $liquidityScores = ['positive' => 10, 'flat' => 5, 'negative' => 0];
        $liquidityScore = $liquidityScores[$liquidityTrend] ?? 5;

        // 7) Supplier Feedback & External Ratings (weight 10%)
        // Placeholder: Assume rating out of 10, default 7
        $supplierRating = Product::where('user_id', $customer->user_id)->sum('rating');
        $supplierScore = $supplierRating * 2; // directly out of 10 // I add *2 because we are working with out of 5 not out of 10

        // Calculate final composite score (sum of weighted scores)
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
}
