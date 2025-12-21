<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CustomerAndSalesController extends Controller
{
    public function saleReport()
    {
        $customers = Customer::with(['orders' => function ($query) {
            $query->where('delivery_status', 'delivered');
        }])->paginate(10);

        $customers->getCollection()->transform(function ($customer) {
            foreach ($customer->orders as $order) {
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

                $serviceFee = $base * ($commissionPct / 100);
                $totalAmount = $base + $serviceFee + $commissionAmount + $commissionTaxAmt;

                $order->calculated = [
                    'base' => $base,
                    'shipping' => $shipping,
                    'discount' => $discount,
                    'tax' => $tax,
                    'commissionAmount' => $commissionAmount,
                    'commissionTaxAmt' => $commissionTaxAmt,
                    'serviceFee' => $serviceFee,
                    'totalAmount' => $totalAmount,
                ];
            }

            return $customer;
        });

        return view('admin.customer-and-sales.sale-report', compact('customers'));
    }

    public function totalSaleReport()
    {
        $orders = Order::where('delivery_status', 'delivered')
            ->select('invoice_number', 'grand_total', 'shipping_cost', 'created_at', 'product_details', 'coupon_discount')
            ->orderBy('created_at')
            ->paginate(10);

        $invoiceCount = $orders->count();
        $invoiceFrom = $orders->first()?->invoice_number ?? '-';
        $invoiceTo = $orders->last()?->invoice_number ?? '-';

        $orderDateFrom = $orders->first()?->created_at->format(dateFormat()) ?? '-';
        $orderDateTo = $orders->last()?->created_at->format(dateFormat()) ?? '-';

        $grandTotal = $shippingTotal = $serviceFee = $taxAmount = $totalInvoice = 0;

        $orders->getCollection()->transform(function ($order) use (
            &$grandTotal,
            &$shippingTotal,
            &$serviceFee,
            &$taxAmount,
            &$totalInvoice
        ) {
            $productDetails = $order->product_details ?? '[]';

            $items = map_product_details($productDetails);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax = calculate_order_tax($order);

            $base = $subTotal + $tax + $shipping - $discount;

            $commissionPct = get_system_commission();
            $commissionAmount = $base * ($commissionPct / 100);

            $commissionTaxPct = get_commission_tax();
            $commissionTaxAmt = $commissionAmount * ($commissionTaxPct / 100);

            $serviceFee = $base * ($commissionPct / 100);
            $totalAmount = $base + $serviceFee + $commissionAmount + $commissionTaxAmt;

            $grandTotal += $base;
            $shippingTotal += $shipping;
            $taxAmount += $tax;
            $totalInvoice += $totalAmount;

            $order->calculated = [
                'base' => $base,
                'shipping' => $shipping,
                'discount' => $discount,
                'tax' => $tax,
                'commissionAmount' => $commissionAmount,
                'commissionTaxAmt' => $commissionTaxAmt,
                'serviceFee' => $serviceFee,
                'totalAmount' => $totalAmount,
            ];

            return $order;
        });

        return view('admin.customer-and-sales.total-sale-report', compact(
            'invoiceCount',
            'invoiceFrom',
            'invoiceTo',
            'orderDateFrom',
            'orderDateTo',
            'grandTotal',
            'shippingTotal',
            'taxAmount',
            'totalInvoice'
        ));
    }

    public function collectionReport()
    {
        $transactions = Transaction::with(['user.customer', 'order'])
            ->whereNotNull('order_id')
            ->paginate(10);

        return view('admin.customer-and-sales.collection-report', compact('transactions'));
    }

    public function detailedCustomerDebt(Request $request)
    {
        $userId = null;

        if ($request->filled('customer_id')) {
            try {
                $userId = Crypt::decrypt($request->get('customer_id'));
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                abort(400, 'Invalid customer ID.');
            }
        }

        $ordersQuery = Order::with(['transactions', 'customer'])
            ->where('delivery_status', 'delivered');

        if ($userId) {
            $ordersQuery->where('user_id', $userId);
        }

        $orders = $ordersQuery->paginate(10);
        $groupedByUser = $orders->groupBy('user_id');

        $summary = $groupedByUser->map(function ($orders, $userId) {
            $order = $orders->first();
            $customer = $order->customer;

            $taxNumber = optional($customer?->customer)->tax_number ?? 'N/A';
            $collected = (float) Transaction::where('user_id', $userId)->sum('collected');

            $totalInvoices = $orders->sum(function ($order) {
                $items = map_product_details($order->product_details);
                $subTotal = $items->sum('total');
                $tax = calculate_order_tax($order);
                $shipping = $order->shipping_cost ?? 0;
                $discount = $order->coupon_discount ?? 0;

                return $subTotal + $tax + $shipping - $discount;
            });

            $remaining = $totalInvoices - $collected;
            $customerName = trim(optional($customer)->first_name . ' ' . optional($customer)->last_name);

            return [
                'customer_name' => $customerName,
                'customer_business' => optional($customer)->business_name,
                'tax_number' => $taxNumber,
                'total_invoice' => $totalInvoices,
                'total_collected' => $collected,
                'remaining_balance' => $remaining,
            ];
        });

        $customers = Customer::with('user')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->user_id,
                    'business_name' => optional($customer->user)->business_name,
                ];
            })
            ->sortBy('business_name')
            ->values();

        return view('admin.customer-and-sales.detailed-customer-debt', [
            'summary' => $summary,
            'paginator' => $orders,
            'customers' => $customers,
            'selectedCustomer' => $userId,
        ]);
    }

    public function totalCustomerDebt()
    {
        $totalInvoices = Order::where('delivery_status', 'delivered')->get()->reduce(function ($carry, $order) {
            $items = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $tax = calculate_order_tax($order);
            $shipping = $order->shipping_cost ?? 0;
            $discount = $order->coupon_discount ?? 0;

            return $carry + ($subTotal + $tax + $shipping - $discount);
        }, 0);

        $totalCollected = Transaction::sum('collected');
        $remainingDebt = $totalInvoices - $totalCollected;
        $openingBalance = 0;

        return view('admin.customer-and-sales.total-customer-debt', [
            'totalInvoices' => $totalInvoices,
            'totalCollected' => $totalCollected,
            'remainingDebt' => $remainingDebt,
            'openingBalance' => $openingBalance,
        ]);
    }
}
