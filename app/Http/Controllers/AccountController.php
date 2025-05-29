<?php

namespace App\Http\Controllers;

use App\Models\{BusinessCategory, Customer, CustomerCreditLimit, Merchant, Order, Package, Payment, Product, SchedulePayment, ShopSetting, SupplierBank, Transaction, Wallet};
use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use App\Traits\EmailSender;
use App\Traits\SmsSender;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AccountController extends Controller
{
    use SmsSender, EmailSender;
    /**
     * Shared calculation for total order amount.
     *
     * @param \Illuminate\Support\Collection|array $orders
     * @return float
     */
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
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
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
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
            ->first();

        $data = $creditService->assess($id);
        $riskScore = $riskService->calculateForUser($customer->user);

        if (!$customer) {
            return redirect()->route('customers')->with('error', __('Customer not found or not assigned to you.'));
        }

        if (empty($customer->cr_data) && $customer->cr_number) {
            $customer->cr_data = app('App\Services\WathqService')->fetchCrData($customer->cr_number);
            $customer->save();
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

        Customer::where('user_id', $user)
            ->firstOrFail()
            ->update(['package_id' => $request->package_id]);

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
        $customer->update(['status' => $status]);

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

    // ---- Supplier Methods (similarly optimized) ----

    public function suppliers()
    {
        $user = currentUser();

        $merchants = Merchant::with('user', 'businessType', 'assigned')
            ->select('id', 'user_id', 'business_type_id', 'cr_number', 'status', 'assigned_to')
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
            ->orderByRaw('ISNULL(assigned_to) DESC')
            ->paginate(10);

        return view('admin.accounts.suppliers', compact('merchants'));
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
            ->when($user->user_type !== 'admin', function ($query) use ($user) {
                $query->where('assigned_to', $user->id);
            })
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
            $merchant->goverment_data = app('App\Services\WathqService')->fetchCrData($merchant->cr_number);
            $merchant->save();
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

    public function updateSupplierStatus(Request $request, $id)
    {
        $status = $request->validate([
            'status' => 'required|in:approved,suspended,pending,blacklisted',
        ])['status'];

        $merchant = Merchant::where('user_id', $id)->firstOrFail();
        $merchant->status = $status;
        $merchant->save();

        $merchant->update(['status' => $status]);

        if ($status === 'approved') {

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
        }

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

            $supplierDue = \App\Models\Wallet::where('seller_id', $order->seller_id)
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
        $data = $service->assess($id);
        return view('admin.accounts.customer-credit', $data);
    }
}
