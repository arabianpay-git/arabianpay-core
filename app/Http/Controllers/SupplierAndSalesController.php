<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class SupplierAndSalesController extends Controller
{
    /**
     * Extract common base calculation
     */
    private function calculateBase(Order $order): float
    {
        $items    = map_product_details($order->product_details);
        $subTotal = $items->sum('total');
        $shipping = $order->shipping_cost   ?? 0;
        $discount = $order->coupon_discount ?? 0;
        $tax      = calculate_order_tax($order);

        return $subTotal + $tax + $shipping - $discount;
    }

    /**
     * Extract common commission calculation
     */
    private function calculateCommission(float $base, $userId): array
    {
        $commissionPct    = get_seller_commission($userId);
        $commissionAmount = $base * ($commissionPct / 100);
        $commissionTaxPct = get_commission_tax();
        $commissionTaxAmt = $commissionAmount * ($commissionTaxPct / 100);
        $totalAmount      = $base + $commissionAmount + $commissionTaxAmt;

        return compact(
            'commissionPct',
            'commissionAmount',
            'commissionTaxPct',
            'commissionTaxAmt',
            'totalAmount'
        );
    }

    public function detailPurchases()
    {
        $orders = Order::where('delivery_status', 'delivered')->latest()->paginate(10);

        $orders->getCollection()->transform(function ($order) {
            // compute item details explicitly
            $items    = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost   ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax      = calculate_order_tax($order);

            // compute base and commission
            $base       = $this->calculateBase($order);
            $commission = $this->calculateCommission($base, $order->seller_id);

            // supplier payments so far
            $supplierDue     = Wallet::where('seller_id', $order->seller_id)
                ->where('order_id', $order->id)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');
            $totalSuplierDue = $base - $supplierDue;

            $order->calculated = array_merge([
                'subTotal'        => $subTotal,
                'shipping'        => $shipping,
                'discount'        => $discount,
                'tax'             => $tax,
                'base'            => $base,
                'supplierDue'     => $supplierDue,
                'totalSuplierDue' => $totalSuplierDue,
            ], $commission);

            return $order;
        });

        return view('admin.supplier-and-sales.detailed-purchases', compact('orders'));
    }

    public function totalPurchases()
    {
        $orders = Order::with('seller')
            ->where('delivery_status', 'delivered')
            ->latest()
            ->get()
            ->groupBy('seller_id');

        $summaryData = [];

        foreach ($orders as $sellerId => $sellerOrders) {
            $user         = optional($sellerOrders->first()->seller);
            $invoiceCount = $sellerOrders->count();
            $invoiceValue = 0;
            $taxTotal     = 0;
            $grandTotal   = 0;

            foreach ($sellerOrders as $order) {
                $base       = $this->calculateBase($order);
                $commission = $this->calculateCommission($base, $order->seller_id);

                $invoiceValue += $base;
                $tax          = calculate_order_tax($order);
                $taxTotal     += $tax;
                $grandTotal   += $commission['totalAmount'];
            }

            $summaryData[] = [
                'user_id'       => $sellerId,
                'first_name'    => $user->first_name,
                'last_name'     => $user->last_name,
                'business_name' => $user->business_name,
                'invoice_count' => $invoiceCount,
                'invoice_value' => $invoiceValue,
                'tax_total'     => $taxTotal,
                'grand_total'   => $grandTotal,
            ];
        }

        $page       = request()->get('page', 1);
        $perPage    = 10;
        $collection = collect($summaryData);

        $paginatedSummary = new LengthAwarePaginator(
            $collection->forPage($page, $perPage),
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.supplier-and-sales.total-purchases', ['summary' => $paginatedSummary]);
    }

    public function paymentOfSupplier(Request $request)
    {
        $paginator = Wallet::with(['seller.merchant', 'order'])
            ->where('transaction_type', 'seller_payment')
            ->latest()
            ->paginate(10);

        $summary = $paginator->getCollection()->map(function (Wallet $wallet) {
            $order           = $wallet->order;
            $merchant        = optional($wallet->seller->merchant);
            $base            = $this->calculateBase($order);
            $commission      = $this->calculateCommission($base, $order->seller_id);

            return [
                'seller_name'     => trim($wallet->seller->first_name . ' ' . $wallet->seller->last_name),
                'seller_business' => $wallet->seller->business_name,
                'invoice_number'  => strtoupper($order->invoice_number ?? 'N/A'),
                'payment_date'    => $wallet->updated_at->format(dateFormat()),
                'payment_invoice' => $base,
                'tax_number'      => $merchant->vat_register_number ?? 'N/A',
                'amount_paid'     => $wallet->balance_after,
                'tax_total'       => calculate_order_tax($order),
                'total_bills'     => $commission['totalAmount'],
            ];
        });

        return view('admin.supplier-and-sales.payment-of-suplier', [
            'summary'   => $summary,
            'paginator' => $paginator,
        ]);
    }

    public function detailedSupplierDebt(Request $request)
    {
        $merchantId = null;
        if ($request->filled('merchant_id')) {
            try {
                $merchantId = Crypt::decrypt($request->get('merchant_id'));
            } catch (DecryptException $e) {
                abort(400, 'Invalid merchant ID.');
            }
        }

        $ordersQuery = Order::with(['seller.merchant'])
            ->where('delivery_status', 'delivered');

        if ($merchantId) {
            $ordersQuery->whereHas('seller.merchant', function ($q) use ($merchantId) {
                $q->where('seller_id', $merchantId);
            });
        }

        $orders    = $ordersQuery->latest()->paginate(10);
        $grouped   = $orders->getCollection()->groupBy('seller_id');

        $summary = $grouped->map(function ($sellerOrders, $sellerId) {
            $firstOrder       = $sellerOrders->first();
            $merchant         = optional($firstOrder->seller->merchant);
            $totalPurchases   = $sellerOrders->sum(function ($order) {
                return $this->calculateBase($order);
            });
            $totalPayments    = Wallet::where('seller_id', $sellerId)
                ->where('transaction_type', 'seller_payment')
                ->sum('amount');
            $openingBalance   = Wallet::where('seller_id', $sellerId)
                ->where('transaction_type', 'seller_payment')
                ->sum('balance_after');

            return [
                'seller_name'       => optional($firstOrder->seller)->first_name . ' ' . optional($firstOrder->seller)->last_name,
                'seller_business'   => optional($firstOrder->seller)->business_name,
                'tax_number'        => $merchant->vat_register_number ?? 'N/A',
                'opening_balance'   => $openingBalance,
                'total_purchases'   => $totalPurchases,
                'total_payments'    => $totalPayments,
                'remaining_balance' => $totalPurchases - $totalPayments + $openingBalance,
            ];
        })->values();

        $merchants = Merchant::with('user')
            ->get()
            ->map(function ($m) {
                return [
                    'id'            => $m->id,
                    'business_name' => optional($m->user)->business_name,
                ];
            })
            ->sortBy('business_name')
            ->values();

        return view('admin.supplier-and-sales.detailed-supplier-debt', [
            'summary'          => $summary,
            'paginator'        => $orders,
            'merchants'        => $merchants,
            'selectedMerchant' => $merchantId,
        ]);
    }

    public function totalSupplierDebt()
    {
        $totalPurchases   = Order::where('delivery_status', 'delivered')
            ->get()
            ->reduce(function ($carry, $order) {
                return $carry + $this->calculateBase($order);
            }, 0.0);

        $totalPayments    = Wallet::where('transaction_type', 'seller_payment')->sum('amount');
        $openingBalance   = Wallet::where('transaction_type', 'seller_payment')->sum('balance_after');
        $remainingBalance = $totalPurchases - $totalPayments;

        return view('admin.supplier-and-sales.total-supplier-debt', compact(
            'totalPurchases',
            'totalPayments',
            'openingBalance',
            'remainingBalance'
        ));
    }

    public function supplierEntitilements(Request $request)
    {
        $transactions = Transaction::with(['order', 'user', 'seller'])
            ->withSum('refundRequests as refund_requests_sum', 'refund_amount')
            ->whereNotNull('seller_id')
            ->orderByDesc('id')
            ->paginate(20);

        $transactions->getCollection()->transform(function ($transaction) {
            if ($order = $transaction->order) {
                $base            = $this->calculateBase($order);
                $supplierDue     = Wallet::where('seller_id', $order->seller_id)
                    ->where('order_id', $order->id)
                    ->where('transaction_type', 'seller_payment')
                    ->sum('balance_after');

                $transaction->calculated = [
                    'base'            => $base,
                    'supplierDue'     => $supplierDue,
                    'totalSuplierDue' => $base - $supplierDue,
                ];
            }

            return $transaction;
        });

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
            $tx = Transaction::lockForUpdate()->find($data['transaction_id']);

            $payment = Payment::create([
                'user_id'         => $tx->user_id,
                'seller_id'       => $data['seller_id'],
                'order_id'        => $tx->order_id,
                'amount'          => $data['amount'],
                'payment_details' => json_encode([
                    'transfer_number' => $data['transfer_number'],
                    'payment_date'    => $data['payment_date'],
                    'receipt_path'    => $data['receipt'],
                ]),
                'invoice_number'  => $tx->order->invoice_number,
                'txn_code'        => $data['transfer_number'],
                'payment_status'  => 'paid',
            ]);

            $tx->collected += $data['amount'];
            if ($tx->collected >= ($tx->loan_amount - $tx->subscription_fees)) {
                $tx->settlement_status = 'settled';
            }
            $tx->save();

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
        $sellers = User::where('user_type', 'merchant')
            ->select('id', 'first_name', 'last_name', 'business_name', 'user_type', 'created_at')
            ->with([
                'sales:id,seller_id,settlement_status',
                'sellerWallet' => function ($query) {
                    $query->select('seller_id', 'balance_after');
                },
                'transactions' => function ($query) {
                    $query->select('id', 'seller_id', 'order_id', 'refrence_payment', 'collected', 'retrieved', 'canceled', 'settlement_status')
                        ->with(['order' => function ($query) {
                            $query->select('id', 'created_at');
                        }]);
                }
            ])
            ->paginate(10);

        return view('admin.supplier-and-sales.supplier-accounts', compact('sellers'));
    }

    public function supplierPayouts()
    {
        $sellers = User::with([
            'sellerWallet' => function ($query) {
                $query->select('id', 'seller_id', 'order_id', 'amount', 'balance_after', 'transaction_type', 'status', 'created_at')
                    ->where('transaction_type', 'seller_payment');
            },
            'sellerWallet.order' => function ($query) {
                $query->select('id', 'seller_id', 'code', 'invoice_number', 'created_at');
            }
        ])->where('user_type', 'merchant')->paginate(10);


        return view('admin.supplier-and-sales.supplier-payout', compact('sellers'));
    }
}
