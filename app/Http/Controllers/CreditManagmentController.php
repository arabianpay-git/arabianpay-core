<?php

namespace App\Http\Controllers;

use App\Models\CustomerCreditLimit;
use App\Models\RiskScore;
use App\Models\SchedulePayment;
use App\Models\User;
use Illuminate\Http\Request;

class CreditManagmentController extends Controller
{
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

    public function creditProfile(Request $request)
    {
        $query = User::where('user_type', 'user');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%")
                    ->orWhere('phone_number', 'like', "%{$request->search}%")
                    ->orWhere('iqama', 'like', "%{$request->search}%");
            });
        }

        // Eager load credit and orders
        $customers = $query->with([
            'customerCreditLimit',
            'orders' => function ($q) {
                $q->where('delivery_status', 'delivered');
            },
        ])->paginate(15);

        // Add calculated fields
        foreach ($customers as $customer) {
            $orders = $customer->orders ?? collect();
            $customer->total_used = $this->calculateTotalOrderAmount($orders);

            $limit = $customer->customerCreditLimit->limit_arabianpay_after ?? 0;
            $customer->limit_remaining = max(0, $limit - $customer->total_used);

            // Repayment history: total paid amount from schedule payments
            $customer->repayment_history = SchedulePayment::where('user_id', $customer->id)
                ->where('payment_status', 'paid')
                ->sum('instalment_amount');

            // Credit Score Calculation
            $used = $customer->total_used ?? 0;
            $repaid = $customer->repayment_history ?? 0;
            $remaining = max(0, $limit - $used);

            $utilizationPct = $limit > 0 ? ($used / $limit) : 0;

            $utilScore = 40 * (1 - min(1, $utilizationPct)); // lower usage = higher score
            $repaymentScore = $limit > 0 ? 40 * min(1, $repaid / $limit) : 0; // more repaid = higher score
            $remainingScore = $limit > 0 ? 20 * ($remaining / $limit) : 0;

            $customer->credit_score = round($utilScore + $repaymentScore + $remainingScore);
        }

        return view('admin.credit-managment.profiles', ['customers' => $customers]);
    }

    public function creditLimit(Request $request)
    {
        $search = $request->input('search');

        $creditLimits = CustomerCreditLimit::whereHas('user', function ($query) use ($search) {
            $query->where('user_type', 'user');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%")
                        ->orWhere('iqama', 'like', "%{$search}%");
                });
            }
        })
            ->with('user')
            ->paginate(10)
            ->appends(['search' => $search]); // keep query string in pagination

        return view('admin.credit-managment.limits', compact('creditLimits'));
    }

    public function repaymentSchedule(Request $request)
    {
        $search = $request->input('search');

        $schedulePayments = SchedulePayment::with(['assigned', 'user'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where(function ($s) use ($search) {
                        $s->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%")
                            ->orWhere('iqama', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('due_date', 'asc')
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.credit-managment.repayment', compact('schedulePayments'));
    }
}
