<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Wallet;
use Arr;
use Carbon\Carbon;

class FahmanController extends Controller
{
    public function fahmanResults($id)
    {
        $customer = Customer::findOrFail($id);
        $orders = Order::where('user_id', $customer->id)->orderBy('created_at')->get();
        $creditScore = $this->calculateCreditScore($orders, $customer);
        $creditScore = $creditScore['compositeScore'];

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

        return view('admin.accounts.partials.fahamn_results', compact('creditScore', 'fahman','customer'));
    }

    public function fahmanDetails($id){
        $customer = Customer::findOrFail($id);
        $orders = Order::where('user_id', $customer->id)->orderBy('created_at')->get();
        $creditScore = $this->calculateCreditScore($orders, $customer);
        $scoreComponents = [
            'POS Revenue'       => $creditScore['monthlyPOSScore'],
            'Industry Risk'     => $creditScore['industryRiskScore'],
            'Repayment'         => $creditScore['repaymentScore'],
            'Business Age'      => $creditScore['businessAgeScore'],
            'Obligations'       => $creditScore['obligationsScore'],
            'Liquidity'         => $creditScore['liquidityScore'],
            'Supplier Ratings'  => $creditScore['supplierScore'],
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

        return view('admin.accounts.partials.fahman_details', compact('scoreComponents','scoreMaxValues', 'interpretations', 'customer'));

    }

    private function calculateCreditScore($orders, $customer = null)
    {
        // dd($customer->user_id);
        // 1) Monthly POS Revenue (weight 25%)
        // Sum total revenue from orders, assume 'total_amount' column or similar
        $monthlyRevenue = Wallet::where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Normalize monthlyRevenue by 50,000 as per formula in PDF
        $monthlyPOSScore = min($monthlyRevenue / 50000, 1) * 25;

        // 2) Business Age & Stability (weight 10%)
        // Use businessAge string from formatBusinessAge - convert to years approx
        // Assume businessAge format like '2.4 Year', '3 Month', or '20 Days'
        $businessAge = 0;
        if ($customer) {
            $issueDateStr = Arr::get(is_array($customer->goverment_data) ? $customer->goverment_data : json_decode($customer->goverment_data, true), 'issueDateGregorian');
            $startDate    = $issueDateStr ? Carbon::parse($issueDateStr) : $customer->created_at;
            $interval = $startDate->diffAsCarbonInterval(Carbon::now());
            $businessAge = $interval->y + ($interval->m / 12) + ($interval->d / 365);
        }

        if ($businessAge >= 3) {
            $businessAgeScore = 10;
        } elseif ($businessAge >= 1) {
            $businessAgeScore = 7;
        } else {
            $businessAgeScore = 5;
        }
        $businessAgeScore *= 1; // 10% weight = max 10 points, so already correct.

        // 3) Industry/Market Risk (weight 15%)
        // Placeholder: Assume merchant has a riskLevel attribute or default to Medium

        $industryRisk = $customer && isset($customer->businessType->risk_level) ? $customer->businessType->risk_level : 'Medium';
        $industryRiskScores = ['low' => 15, 'medium' => 10, 'high' => 5];
        $industryRiskScore = $industryRiskScores[$industryRisk] ?? 10;

        // 4) Existing Financial Obligations (weight 10%)
        // Placeholder: Assume $existingDebt in local variable, for now set to 0 (no debt)
        $totalPurchases   = Order::where('user_id', $customer->user_id)->where('delivery_status', 'delivered')
            ->get()
            ->reduce(function ($carry, $order) {
                return $carry + $this->calculateBase($order);
            }, 0.0);
        $totalPayments    = Wallet::where('transaction_type', 'user_repayment')->sum('amount');

        $existingDebt = $totalPurchases - $totalPayments;
        // Formula: 10 - min(Monthly Revenue / Existing Debt, 10), if debt=0, max score 10
        if ($existingDebt > 0) {
            $obligationsScore = 10 - min($monthlyRevenue / $existingDebt, 10);
        } else {
            $obligationsScore = 10;
        }

        // 5) Repayment Behavior (weight 20%)
        // Placeholder: Assume repaymentDelays count from SIMAH or history, default 1 delay
        $repaymentDelays = Transaction::where('user_id', $customer->user_id)->count('payment_status');
        if ($repaymentDelays == 0) {
            $repaymentScore = 20;
        } elseif ($repaymentDelays <= 2) {
            $repaymentScore = 15;
        } else {
            $repaymentScore = 5;
        }


        // 6) Bank Balance & Liquidity Trend (weight 10%)
        // Placeholder: Assume positive trend, flat, or negative
        $liquidityTrend = 'positive'; // options: positive, flat, negative
        $liquidityScores = ['positive' => 10, 'flat' => 5, 'negative' => 0];
        $liquidityScore = $liquidityScores[$liquidityTrend] ?? 5;

        // 7) Supplier Feedback & External Ratings (weight 10%)
        // Placeholder: Assume rating out of 10, default 7
        $supplierRating = Product::where('user_id', $customer->user_id)->sum('rating');
        $supplierScore = $supplierRating * 2; // directly out of 10 // I add *2 because we are working with out of 5 not out of 10

        // Calculate final composite score (sum of weighted scores)
        $compositeScore = $monthlyPOSScore
            + $businessAgeScore
            + $industryRiskScore
            + $obligationsScore
            + $repaymentScore
            + $liquidityScore
            + $supplierScore;

        return [
            'monthlyRevenue' => round($monthlyRevenue, 2),
            'monthlyPOSScore' => round($monthlyPOSScore, 2),
            'businessAge' => round($businessAge, 2),
            'businessAgeScore' => round($businessAgeScore, 2),
            'industryRisk' => ucfirst($industryRisk),
            'industryRiskScore' => round($industryRiskScore, 2),
            'existingDebt' => round($existingDebt, 2),
            'obligationsScore' => round($obligationsScore, 2),
            'repaymentDelays' => $repaymentDelays,
            'repaymentScore' => round($repaymentScore, 2),
            'liquidityTrend' => $liquidityTrend,
            'liquidityScore' => round($liquidityScore, 2),
            'supplierRating' => round($supplierRating, 2),
            'supplierScore' => round($supplierScore, 2),
            'compositeScore' => round($compositeScore, 2),
        ];
    }

     private function calculateBase(Order $order): float
        {
            $items    = map_product_details($order->product_details);
            $subTotal = $items->sum('total');
            $shipping = $order->shipping_cost   ?? 0;
            $discount = $order->coupon_discount ?? 0;
            $tax      = calculate_order_tax($order);

            return $subTotal + $tax + $shipping - $discount;
        }
}
