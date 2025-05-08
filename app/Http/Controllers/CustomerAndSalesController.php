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
        $customers = Customer::with('orders')->paginate(10);
        return view('admin.customer-and-sales.sale-report', compact('customers'));
    }

    public function totalSaleReport()
    {
        $orders = Order::select('invoice_number', 'grand_total', 'shipping_cost', 'created_at')->orderBy('created_at')->paginate(10);

        $invoiceCount = $orders->count();
        $invoiceFrom  = $orders->first()?->invoice_number ?? '-';
        $invoiceTo    = $orders->last()?->invoice_number ?? '-';

        $orderDateFrom = $orders->first()?->created_at->format('Y-m-d') ?? '-';
        $orderDateTo   = $orders->last()?->created_at->format('Y-m-d') ?? '-';

        $grandTotal    = $orders->sum('grand_total');
        $shippingTotal = $orders->sum('shipping_cost');

        $systemCommission = get_system_commission(0);
        $taxPercent       = get_tax(0);

        $serviceFee  = ($systemCommission / 100) * $grandTotal;
        $taxAmount   = ($taxPercent / 100) * $grandTotal;
        $totalInvoice = $grandTotal + $shippingTotal + $serviceFee + $taxAmount;

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

        $ordersQuery = Order::with(['transactions', 'customer']);

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
            $totalInvoices = (float) $orders->sum('grand_total');
            $remaining = $totalInvoices - $collected;

            $customerName = trim(optional($customer)->first_name . ' ' . optional($customer)->last_name);

            return [
                'customer_name'       => $customerName,
                'customer_business'   => optional($customer)->business_name,
                'tax_number'          => $taxNumber,
                'total_invoice'       => $totalInvoices,
                'total_collected'     => $collected,
                'remaining_balance'   => $remaining,
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
            'summary'   => $summary,
            'paginator' => $orders,
            'customers' => $customers,
            'selectedCustomer' => $userId,
        ]);
    }

    public function totalCustomerDebt()
    {
        // Get the total amount for all orders (grand_total) and total collected from transactions
        $totalInvoices = Order::sum('grand_total'); // Sum of all orders' grand totals
        $totalCollected = Transaction::sum('collected'); // Sum of all collected amounts

        // Calculate the remaining debt
        $remainingDebt = $totalInvoices - $totalCollected;

        // Get the opening balance (if any)
        $openingBalance = 0; // Set your logic to get the opening balance, if applicable

        // Pass the totals to the view
        return view('admin.customer-and-sales.total-customer-debt', [
            'totalInvoices' => $totalInvoices,
            'totalCollected' => $totalCollected,
            'remainingDebt' => $remainingDebt,
            'openingBalance' => $openingBalance, // Include opening balance if needed
        ]);
    }
}
