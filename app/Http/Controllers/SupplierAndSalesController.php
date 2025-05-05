<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SchedulePayment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierAndSalesController extends Controller
{
    public function detailPurchases()
    {
        $orders = Order::latest()->paginate(10);
        return view('admin.supplier-and-sales.detailed-purchases', compact('orders'));
    }

    public function totalPurchases()
    {
        $orders = Order::with('user')->latest()->get()->groupBy('user_id');

        $summaryData = [];

        foreach ($orders as $userId => $userOrders) {
            $user = optional($userOrders->first()->user);

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
                'invoice_number'            => strtoupper($order->uuid ?? 'N/A'),
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
}
