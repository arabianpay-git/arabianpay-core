<?php

namespace App\Http\Controllers;

use App\Models\BusinessCategory;
use App\Models\Customer;
use App\Models\CustomerCreditLimit;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SchedulePayment;
use App\Models\ShopSetting;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AccountController extends Controller
{
    public function customers()
    {
        $customers = Customer::with('user', 'package')
            ->select('id', 'user_id', 'package_id', 'cr_number', 'address', 'purchasing_volume', 'status', 'created_at')
            ->paginate(10);

        return view('admin.accounts.customer', compact('customers'));
    }

    public function customerProfile($id)
    {
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();

        if (empty($customer->cr_data) && !empty($customer->cr_number)) {
            try {
                $response = Http::withHeaders([
                    // swagger says 'apiKey' in header
                    'apiKey' => env('API_KEY_WATHQ'),
                    'Accept' => 'application/json',
                ])->get(sprintf(
                    'https://%s/commercial-registration/fullinfo/%s',
                    env('BASE_URL_WATHQ'),
                    $customer->cr_number
                ), [
                    'language' => 'en'
                ]);

                // cache whatever we get (success or not)
                if ($response->successful()) {
                    $customer->goverment_data = $response->json();
                } else {
                    $customer->goverment_data = [
                        'status' => $response->status(),
                        'body'   => $response->json() ?: $response->body(),
                    ];
                    Log::warning("WAT-HQ API returned {$response->status()} for CR {$customer->cr_number}");
                }

                $customer->save();
            } catch (\Exception $e) {
                $customer->goverment_data = [
                    'exception' => $e->getMessage(),
                ];
                $customer->save();
                Log::error("Failed to fetch gov data for CR {$customer->cr_number}: {$e->getMessage()}");
            }
        }

        return view('admin.accounts.customer-profile', compact('customer'));
    }

    public function customerFinance($id)
    {
        $customer = Customer::with('user', 'package')
            ->where('user_id', $id)
            ->firstOrFail();

        $packages = Package::orderBy('name', 'ASC')->get();
        $creditLimitLogs = CustomerCreditLimit::where('user_id', $id)->paginate(10);

        $totalPaymentDueAmount = SchedulePayment::where('user_id', $id)->whereIn('payment_status', ['due', 'late'])->sum('instalment_amount');
        $totalDuePayments = SchedulePayment::where('user_id', $id)->where('payment_status', 'due')->count();
        $totalLatePayments = SchedulePayment::where('user_id', $id)->where('payment_status', 'late')->count();
        return view('admin.accounts.customer-finance', compact('customer', 'packages', 'totalPaymentDueAmount', 'totalDuePayments', 'totalLatePayments', 'creditLimitLogs'));
    }

    public function upgradePackage(Request $request, $user)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);

        $customer = Customer::where('user_id', $user)->firstOrFail();
        $customer->package_id = $request->package_id;
        $customer->save();

        return redirect()->back()->with('success', 'User package upgraded successfully.');
    }

    public function upgradeLimit(Request $request)
    {
        $request->validate([
            'limit_arabianpay_before' => 'required|string|max:255',
            'limit_arabianpay_after' => 'required|string|max:255',
        ]);

        $limit = CustomerCreditLimit::findOrFail($request->credit_limit_id);

        $limit->limit_arabianpay_after = $request->limit_arabianpay_after;
        $limit->limit_arabianpay_before = $request->limit_arabianpay_before;
        $limit->save();

        return redirect()->back()->with('success', 'Customer credit limit updated successfully.');
    }

    public function createCreditLimit(Request $request)
    {
        $request->validate([
            'limit_arabianpay_before' => 'required|numeric',
            'limit_arabianpay_after'  => 'required|numeric',
            'simah_limit'             => 'required|numeric',
        ]);

        $creditLimit = new CustomerCreditLimit();
        $creditLimit->user_id = $request->user_id;
        $creditLimit->user_id = $request->package_id;
        $creditLimit->limit_arabianpay_before = $request->input('limit_arabianpay_before');
        $creditLimit->limit_arabianpay_after  = $request->input('limit_arabianpay_after');
        $creditLimit->simah_limit = $request->input('simah_limit');
        $creditLimit->save();

        return redirect()->back()->with('success', 'Credit Limit created successfully.');
    }

    public function updateCustomerStatus(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:approved,suspended,pending,blacklisted',
        ]);

        $customer->status = $validated['status'];
        $customer->save();

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
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();

        $transactions = Transaction::select($this->selectFields)
            ->with([
                'order' => function ($query) {
                    $query->select('id', 'grand_total', 'shipping_city', 'general_status');
                },
                'user'
            ])
            ->where('user_id', $id)
            ->paginate(10);
        return view('admin.accounts.customer-transactions', compact('customer', 'transactions'));
    }

    public function orders($id)
    {
        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();

        $orders = Order::select(
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
        )
            ->where('user_id', $id)
            ->with(['user', 'seller', 'pickupPoint'])
            ->paginate(10);
        return view('admin.accounts.customer-orders', compact('customer', 'orders'));
    }

    public function payments($id)
    {

        $customer = Customer::with('user')
            ->where('user_id', $id)
            ->firstOrFail();
        $wallets = Wallet::select([
            'id',
            'order_id',
            'amount',
            'balance_after',
            'transaction_type',
            'status',
            'created_at'
        ])
            ->where('user_id', $id)
            ->with([
                'order:id,invoice_number',
            ])
            ->latest()
            ->paginate(10);

        return view('admin.accounts.customer-payments', compact('customer', 'wallets'));
    }

    public function customerBusiness()
    {
        dd('remaning');
    }

    public function customerSimah()
    {
        dd('remaning');
    }

















































    public function suppliers()
    {
        $merchants = Merchant::with('user', 'businessType')
            ->select('id', 'user_id', 'business_type_id', 'cr_number', 'status')
            ->paginate(10);

        return view('admin.accounts.suppliers', compact('merchants'));
    }

    public function supplierProducts($id)
    {
        $merchant = Merchant::with('user', 'businessType')
            ->where('user_id', $id)
            ->firstOrFail();

        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->where('user_id', $id)
            ->select(['id', 'name', 'thumbnail', 'unit_price', 'brand_id', 'current_stock', 'approved', 'published', 'created_at'])
            ->latest()
            ->paginate(10);
        return view('admin.accounts.suppliers-products', compact('merchant', 'products'));
    }

    public function supplierProfile($id)
    {
        $merchant = Merchant::with('user', 'businessType')
            ->where('id', $id)
            ->firstOrFail();

        $sellerShop = ShopSetting::where('user_id', $merchant->user_id)->select('address')->first();


        $businessCategory = [];
        if ($merchant->business_category_id) {
            $categoryIds = json_decode($merchant->business_category_id, true);
            $businessCategory = BusinessCategory::whereIn('id', $categoryIds)->get();
        }

        $totalProducts = Product::where('user_id', $merchant->user_id)->count();
        $totalOrders = Order::where('seller_id', $merchant->user_id)->count();
        $revenue = Payment::where('seller_id', $merchant->user_id)->sum('amount');

        if (empty($merchant->goverment_data) && !empty($merchant->cr_number)) {
            try {
                $response = Http::withHeaders([
                    // swagger says 'apiKey' in header
                    'apiKey' => env('API_KEY_WATHQ'),
                    'Accept' => 'application/json',
                ])->get(sprintf(
                    'https://%s/commercial-registration/fullinfo/%s',
                    env('BASE_URL_WATHQ'),
                    $merchant->cr_number
                ), [
                    'language' => 'en'
                ]);

                // cache whatever we get (success or not)
                if ($response->successful()) {
                    $merchant->goverment_data = $response->json();
                } else {
                    $merchant->goverment_data = [
                        'status' => $response->status(),
                        'body'   => $response->json() ?: $response->body(),
                    ];
                    Log::warning("WAT-HQ API returned {$response->status()} for CR {$merchant->cr_number}");
                }

                $merchant->save();
            } catch (\Exception $e) {
                $merchant->goverment_data = [
                    'exception' => $e->getMessage(),
                ];
                $merchant->save();
                Log::error("Failed to fetch gov data for CR {$merchant->cr_number}: {$e->getMessage()}");
            }
        }

        // 5) Render the view
        return view('admin.accounts.supplier-profile', [
            'merchant'         => $merchant,
            'businessCategory' => $businessCategory,
            'totalProducts'    => $totalProducts,
            'sellerShop'       => $sellerShop,
            'totalOrders'      => $totalOrders,
            'revenue'          => $revenue
        ]);
    }

    public function updateSupplierStatus(Request $request, $id)
    {
        $merchant = Merchant::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending,draft,blocked',
        ]);

        $merchant->status = $validated['status'];
        $merchant->save();

        return back()->with('success', 'Status updated successfully!');
    }
}
