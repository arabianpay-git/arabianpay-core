<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SupplierAndSalesController extends Controller
{
    public function detailPurchases()
    {
        $orders = Order::latest()->paginate(10);
        return view('admin.supplier-and-sales.detailed-purchases', compact('orders'));
    }

    public function totalPurchases()
    {
        $orders = Order::with('seller')->latest()->get()->groupBy('seller_id');

        $summaryData = [];

        foreach ($orders as $userId => $userOrders) {
            $user = optional($userOrders->first()->seller);

            $summaryData[] = [
                'user_id'       => $userId,
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'business_name'     => $user->business_name,
                'invoice_count' => $userOrders->count(),
                'invoice_value' => $userOrders->sum('grand_total'),
                'tax_total'     => $userOrders->sum(function ($order) {
                    return calculate_order_tax($order);
                }),
                'grand_total'   => $userOrders->sum(function ($order) {
                    return $order->grand_total + calculate_order_tax($order);
                }),
            ];
        }

        // Convert to Laravel collection
        $summaryCollection = collect($summaryData);

        // Manual pagination
        $page = request()->get('page', 1);
        $perPage = 10;
        $paginatedSummary = new LengthAwarePaginator(
            $summaryCollection->forPage($page, $perPage),
            $summaryCollection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.supplier-and-sales.total-purchases', [
            'summary' => $paginatedSummary
        ]);
    }

    public function paymentOfSupplier()
    {
        $schedulePayments = SchedulePayment::with(['seller', 'order'])
            ->latest()
            ->paginate(10);

        $summary = $schedulePayments->map(function ($payment) {
            $order = $payment->order;

            $merchant = $payment->seller->merchant;
            $taxNumber = optional($merchant)->vat_register_number ?? 'N/A';

            return [
                'seller_name'               => optional($payment->seller)->first_name . ' ' . optional($payment->seller)->last_name,
                'seller_business'           => optional($payment->seller)->business_name,
                'invoice_number'            => strtoupper($order->invoice_number ?? 'N/A'),
                'payment_date'              => $payment->due_date->format('Y-m-d'),
                'payment_invoice_paid'      => $payment->updated_at->format('Y-m-d'),
                'tax_number'                => $taxNumber,
                'amount_paid'               => number_format($payment->instalment_amount, 2),
                'tax_total'                 => calculate_order_tax($order),
                'grand_total'               => $order->grand_total + calculate_order_tax($order),
            ];
        });

        return view('admin.supplier-and-sales.payment-of-suplier', [
            'summary'   => $summary,
            'paginator' => $schedulePayments,
        ]);
    }

    public function detailedSupplierDebt(Request $request)
    {
        $merchantId = null;

        if ($request->filled('merchant_id')) {
            try {
                $merchantId = Crypt::decrypt($request->get('merchant_id'));
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // handle invalid decryption (optional)
                abort(400, 'Invalid merchant ID.');
            }
        }
        $ordersQuery = Order::with(['payments', 'transactions', 'seller.merchant']);

        if ($merchantId) {
            $ordersQuery->whereHas('seller.merchant', function ($query) use ($merchantId) {
                $query->where('seller_id', $merchantId);
            });
        }

        $orders = $ordersQuery->paginate(10);
        $groupedBySeller = $orders->groupBy('seller_id');

        $summary = $groupedBySeller->map(function ($orders, $sellerId) {
            $order = $orders->first();
            $merchant = $order->seller->merchant;
            $taxNumber = optional($merchant)->vat_register_number ?? 'N/A';

            $totalPurchases = (float) $orders->sum('grand_total');
            $totalPayments = (float) Wallet::where('seller_id', $sellerId)
                ->whereIn('transaction_type', ['seller_payment', 'user_repayment'])
                ->sum('amount');
            $openingBalance = (float) Wallet::where('seller_id', $sellerId)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');
            $remainingBalance = $totalPurchases - $totalPayments + $openingBalance;

            return [
                'seller_name'       => optional($order->seller)->first_name . " " . optional($order->seller)->last_name,
                'seller_business'   => optional($order->seller)->business_name,
                'tax_number'        => $taxNumber,
                'opening_balance'   => $openingBalance,
                'total_purchases'   => $totalPurchases,
                'total_payments'    => $totalPayments,
                'remaining_balance' => $remainingBalance,
            ];
        });

        $merchants = Merchant::with('user')
            ->get()
            ->map(function ($merchant) {
                return [
                    'id' => $merchant->id,
                    'business_name' => optional($merchant->user)->business_name,
                ];
            })
            ->sortBy('business_name')
            ->values();

        return view('admin.supplier-and-sales.detailed-supplier-debt', [
            'summary'   => $summary,
            'paginator' => $orders,
            'merchants' => $merchants,
            'selectedMerchant' => $merchantId,
        ]);
    }

    public function totalSupplierDebt()
    {
        // Calculate the totals
        $totalPurchases = (float) Order::sum('grand_total');
        $totalPayments = (float) Wallet::whereIn('transaction_type', ['seller_payment', 'user_repayment'])->sum('amount');
        $openingBalance = (float) Wallet::where('transaction_type', 'seller_payment')->sum('balance_after');
        $remainingBalance = $totalPurchases - $totalPayments + $openingBalance;

        return view('admin.supplier-and-sales.total-supplier-debt', [
            'totalPurchases'   => $totalPurchases,
            'totalPayments'    => $totalPayments,
            'openingBalance'   => $openingBalance,
            'remainingBalance' => $remainingBalance,
        ]);
    }

    public function supplierEntitilements(Request $request)
    {
        $transactions = Transaction::with(['order', 'user', 'seller'])
            ->withSum('refundRequests as refund_requests_sum', 'refund_amount')
            ->whereNotNull('seller_id')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.supplier-and-sales.supplier-entitlement', compact('transactions'));
    }


    public function sellerPaymentFromAdmin(Request $request)
    {
        $data = $request->validate([
            'transaction_id'  => 'required|exists:transactions,id',
            'seller_id'       => 'required|exists:users,id',
            'amount'          => 'required|numeric|min:0.01',
            'transfer_number' => 'required|string|max:255',
            'payment_date'    => 'required|date',
            'receipt'         => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($data) {
            // 1) Load the original transaction
            $tx = Transaction::lockForUpdate()->find($data['transaction_id']);

            // 3) Create the Payment record
            $payment = Payment::create([
                'user_id'        => $tx->user_id,
                'seller_id'      => $data['seller_id'],
                'order_id'       => $tx->order_id,
                'amount'         => $data['amount'],
                'payment_details' => json_encode([
                    'transfer_number' => $data['transfer_number'],
                    'payment_date'    => $data['payment_date'],
                    'receipt_path'    => $data['receipt'],
                ]),
                'invoice_number' => $tx->order->invoice_number,
                'txn_code'       => $data['transfer_number'],
                'payment_status' => 'paid',
            ]);

            // 4) Update the Transaction’s collected & settlement_status
            $tx->collected += $data['amount'];
            if ($tx->collected >= ($tx->loan_amount - $tx->subscription_fees)) {
                $tx->settlement_status = 'settled';
            }
            $tx->save();

            // 5) Create a Wallet entry for the seller
            //    Compute current balance (sum of all previous seller_payments)
            $previousBalance = Wallet::where('seller_id', $data['seller_id'])
                ->where('transaction_type', 'seller_payment')
                ->sum('amount');

            Wallet::create([
                'seller_id'        => $data['seller_id'],
                'order_id'         => $tx->order_id,
                'instalment_id'    => $tx->plan_id,
                'transaction_type' => 'seller_payment',
                'amount'           => $data['amount'],
                'balance_after'    => $previousBalance + $data['amount'],
                'status'           => 'active',
            ]);
        });

        return back()->with('success', 'Supplier payment recorded successfully.');
    }

    public function supplierAccounts()
    {
        // Paginate the sellers instead of the transactions
        $sellers = User::where('user_type', 'merchant')->select('id', 'first_name', 'last_name', 'business_name', 'user_type', 'created_at', 'status')
            ->with(['sellerWallet' => function ($query) {
                $query->select('seller_id', 'balance_after');
            }, 'transactions' => function ($query) {
                $query->select('id', 'seller_id', 'order_id', 'refrence_payment', 'collected', 'retrieved', 'canceled', 'settlement_status')
                    ->with(['order' => function ($query) {
                        $query->select('id', 'created_at');
                    }]);
            }])
            ->where('user_type', 'merchant')
            ->paginate(10); // Now paginating the sellers

        return view('admin.supplier-and-sales.supplier-accounts', compact('sellers'));
    }

    public function supplierPayouts()
    {
        // Use paginate instead of get
        $sellers = User::with(['sellerWallet' => function ($query) {
            $query->select('seller_id', 'amount', 'balance_after', 'transaction_type', 'status', 'created_at')
                ->where('transaction_type', 'seller_payment');
        }])->where('user_type', 'merchant')->paginate(10); // Adjust the number of items per page (10 in this example)

        return view('admin.supplier-and-sales.supplier-payout', compact('sellers'));
    }
}
