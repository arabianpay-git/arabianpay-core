<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\CreditAssessmentService;
use App\Services\RiskAnalyticsService;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class FahmanController extends Controller
{
    public function fahmanResults($id, CreditAssessmentService $creditService)
    {

        $customer = Customer::findOrFail($id);
        $orders = Order::where('user_id', $customer->id)->orderBy('created_at')->get();
        $creditScore = $creditService->assess($customer->user_id);
        $creditScore = $creditScore['creditScore']['compositeScore'];


        // التصنيفات بناءً على الدرجة
        if ($creditScore >= 90) {
            $fahman = [
                'type' => 'success',
                'title' => 'Wow! Seems like a highly reliable customer.',
                'message' => 'You can lend with full confidence.',
            ];
        } elseif ($creditScore >= 75) {
            $fahman = [
                'type' => 'primary',
                'title' => 'Customer looks reliable.',
                'message' => 'Lending is fine with some periodic review.',
            ];
        } elseif ($creditScore >= 60) {
            $fahman = [
                'type' => 'warning',
                'title' => 'Customer is somewhat unstable.',
                'message' => 'Lend with conditions or guarantees.',
            ];
        } elseif ($creditScore >= 40) {
            $fahman = [
                'type' => 'danger',
                'title' => 'I am worried about this customer.',
                'message' => 'Lending involves risk. Proceed cautiously.',
            ];
        } else {
            $fahman = [
                'type' => 'danger',
                'title' => 'High credit risk!',
                'message' => 'Do not lend. Financial data is unfavorable.',
            ];
        }

        return view('admin.accounts.partials.fahamn_results', compact('creditScore', 'fahman', 'customer'));
    }

    public function fahmanSupplierResults($id, RiskAnalyticsService $riskAnalyticsService)
    {

        $merchant = Merchant::findOrFail($id);
        $riskScore = $riskAnalyticsService->calculateForUser($merchant->user);
        $score = $riskScore->total_score;

        $fahmanAdvice = match (true) {
            $score >= 90 => [
                'level' => 'success',
                'title' => 'Trusted and proven supplier',
                'message' => 'This supplier has excellent financial and operational stability. You can proceed with full confidence.',
            ],
            $score >= 75 => [
                'level' => 'primary',
                'title' => 'Reliable supplier',
                'message' => 'Generally safe to deal with. Maintain occasional review and monitoring.',
            ],
            $score >= 60 => [
                'level' => 'warning',
                'title' => 'Acceptable, but watch closely',
                'message' => 'Some risk factors are present. Set limits and review documentation if necessary.',
            ],
            $score >= 40 => [
                'level' => 'danger',
                'title' => 'High risk supplier',
                'message' => 'Multiple warning signs found. Proceed only with guarantees or risk controls.',
            ],
            default => [
                'level' => 'danger',
                'title' => 'Critical risk',
                'message' => 'Fahman advises against working with this supplier at this time. Their data indicates high instability.',
            ],
        };
        
        return view('admin.accounts.partials.fahamn_supplier_results', compact('riskScore',  'fahmanAdvice'));
    }

    public function fahmanDetails($id, CreditAssessmentService $creditService)
    {
        $customer = Customer::findOrFail($id);
        $orders = Order::where('user_id', $customer->id)->orderBy('created_at')->get();
        $creditScore = $creditService->assess($customer->user_id);
        $scoreComponents = [
            'POS Revenue'       => $creditScore['creditScore']['monthlyPOSScore'],
            'Industry Risk'     => $creditScore['creditScore']['industryRiskScore'],
            'Repayment'         => $creditScore['creditScore']['repaymentScore'],
            'Business Age'      => $creditScore['creditScore']['businessAgeScore'],
            'Obligations'       => $creditScore['creditScore']['obligationsScore'],
            'Liquidity'         => $creditScore['creditScore']['liquidityScore'],
            'Supplier Ratings'  => $creditScore['creditScore']['supplierScore'],
        ];

        $scoreMaxValues = [
            'POS Revenue'       => 25,
            'Industry Risk'     => 15,
            'Repayment'         => 20,
            'Business Age'      => 10,
            'Obligations'       => 10,
            'Liquidity'         => 10,
            'Supplier Ratings'  => 10,
        ];

        $interpretations = [];
        foreach ($scoreComponents as $key => $value) {
            $interpretations[$key] = match (true) {
                $value >= 20 => 'Excellent — this boosts Fahman’s confidence.',
                $value >= 15 => 'Good performance, but could still be improved.',
                $value >= 10 => 'Some instability detected — better to monitor closely.',
                $value >= 5  => 'Moderate risk — caution is advised.',
                default      => 'Very weak — this raises serious concern.',
            };
        }

        return view('admin.accounts.partials.fahman_details', compact('scoreComponents', 'scoreMaxValues', 'interpretations', 'customer'));
    }

     public function fahmanSupplierDetails($id, RiskAnalyticsService $riskAnalyticsService)
    {

        $merchant = Merchant::findOrFail($id);
        $riskScore = $riskAnalyticsService->calculateForUser($merchant->user);
        
        return view('admin.accounts.partials.fahamn_supplier_details', compact('riskScore'));
    }
}
