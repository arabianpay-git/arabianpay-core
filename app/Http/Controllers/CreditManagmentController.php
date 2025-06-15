<?php

namespace App\Http\Controllers;

use App\Models\CustomerCreditLimit;
use App\Models\RiskScore;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use Illuminate\Http\Request;

class CreditManagmentController extends Controller
{

    protected $creditAssesmentService;
    protected $riskAnalyticsService;

    public function __construct(
        CreditAssessmentService $creditAssesmentService,
        RiskAnalyticsService $riskAnalyticsService
    ) {
        $this->creditAssesmentService = $creditAssesmentService;
        $this->riskAnalyticsService = $riskAnalyticsService;
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

    private function getCustomersWithCreditData($search = null, $perPage = 10)
    {
        $query = User::where('user_type', 'user');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('iqama', 'like', "%{$search}%");
            });
        }

        $customers = $query->with([
            'customerCreditLimit',
            'orders' => function ($q) {
                $q->where('delivery_status', 'delivered');
            },
        ])->paginate($perPage);

        foreach ($customers as $customer) {
            $orders = $customer->orders ?? collect();
            $customer->total_used = $this->calculateTotalOrderAmount($orders);

            $creditScoreService = $this->creditAssesmentService->assess($customer->id);
            $riskScoreService = $this->riskAnalyticsService->calculateForUser($customer);

            $creditScore = $creditScoreService['creditScore']['compositeScore'] ?? 0;
            $riskScore = $riskScoreService->total_score ?? 0;

            $oldCreditLimit = 20000;

            $finalScore = $creditScore * ($riskScore / 100);

            $newCreditLimit = $oldCreditLimit * ($finalScore / 100);

            $remainingCreditLimit = max(0, $newCreditLimit - $customer->total_used);

            $customer->creditLimit = $newCreditLimit;
            $customer->limit_remaining = $remainingCreditLimit;
            $customer->repayment_history = SchedulePayment::where('user_id', $customer->id)
                ->where('payment_status', 'paid')
                ->sum('instalment_amount');

            $customer->credit_score = round($creditScore);
        }

        return $customers;
    }

    public function creditProfile(Request $request)
    {
        $search = $request->input('search');
        $customers = $this->getCustomersWithCreditData($search, 10);
        return view('admin.credit-managment.profiles', compact('customers'));
    }

    public function creditLimit(Request $request)
    {
        $search = $request->input('search');
        $creditLimits = $this->getCustomersWithCreditData($search, 10);

        return view('admin.credit-managment.limits', ['creditLimits' => $creditLimits]);
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
