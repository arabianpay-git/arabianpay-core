<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Arr;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class CreditAssessmentService
{
    public function assess(int $userId): array
    {
        $customer = $this->getCustomer($userId);
        $orders = $this->getOrders($customer->id);
        $businessAge = $this->formatBusinessAge($this->getBusinessStartDate($customer));

        $creditScore = $this->calculateCreditScore($orders, $customer);

        $riskLevel = $this->determineRiskLevel($creditScore['compositeScore']);
        $scoreComponents = $this->mapScoreComponents($creditScore);
        $paymentTimeline = $this->getPaymentTimeline($customer->user_id);
        $riskFactors = $this->getRiskFactors();
        $complianceStatus = $this->getComplianceStatus($customer);

        return compact(
            'customer',
            'orders',
            'businessAge',
            'creditScore',
            'riskLevel',
            'scoreComponents',
            'paymentTimeline',
            'riskFactors',
            'complianceStatus'
        );
    }

    public function assessAll(): Collection
    {
        return Customer::with('user')->get()->map(function ($customer) {
            $orders = $this->getOrders($customer->id);
            $businessAge = $this->formatBusinessAge($this->getBusinessStartDate($customer));
            $creditScore = $this->calculateCreditScore($orders, $customer);

            return [
                'user_id' => $customer->user_id,
                'name' => $customer->user->name,
                'compositeScore' => $creditScore['compositeScore'],
                'riskLevel' => $this->determineRiskLevel($creditScore['compositeScore']),
                'businessAge' => $businessAge,
            ];
        });
    }

    private function getCustomer(int $userId): Customer
    {
        return Customer::with(['user', 'businessType'])
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    private function getOrders(int $customerId): Collection
    {
        return Order::where('user_id', $customerId)
            ->orderBy('created_at')
            ->get();
    }

    private function getBusinessStartDate(Customer $customer): Carbon
    {
        $crData = is_array($customer->cr_data)
            ? $customer->cr_data
            : json_decode($customer->cr_data, true);

        $issueDate = Arr::get($crData, 'issueDateGregorian');

        return $issueDate
            ? Carbon::parse($issueDate)
            : $customer->created_at;
    }

    private function formatBusinessAge(Carbon $start): string
    {
        $interval = $start->diffAsCarbonInterval(now());

        if ($interval->y > 0) {
            return round($interval->y + ($interval->m / 12), 1) . ' Years';
        }

        return $interval->m > 0
            ? $interval->m . ' Months'
            : $interval->d . ' Days';
    }

    private function calculateCreditScore(Collection $orders, Customer $customer): array
    {
        $monthlyRevenue = Wallet::where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $monthlyPOSScore = min($monthlyRevenue / 50000, 1) * 25;

        // Business age calculation
        $startDate = $this->getBusinessStartDate($customer);
        $interval = $startDate->diffAsCarbonInterval(now());
        $businessAge = $interval->y + ($interval->m / 12) + ($interval->d / 365);

        $businessAgeScore = match (true) {
            $businessAge >= 3 => 10,
            $businessAge >= 1 => 7,
            default => 5
        };

        // Industry risk
        $industryRisk = strtolower($customer->businessType->risk_level ?? 'medium');
        $industryRiskScore = match ($industryRisk) {
            'low' => 15,
            'high' => 5,
            default => 10
        };

        // Financial obligations
        $totalPurchases = $orders->where('delivery_status', 'delivered')->sum(function ($order) {
            return $this->calculateOrderBase($order);
        });

        $totalPayments = Wallet::where('user_id', $customer->user_id)
            ->where('transaction_type', 'user_repayment')
            ->sum('amount');

        $existingDebt = max($totalPurchases - $totalPayments, 0);
        $obligationsScore = ($existingDebt > 0)
            ? 10 - min($monthlyRevenue / $existingDebt, 10)
            : 10;

        // Repayment behavior
        $repaymentDelays = Transaction::where('user_id', $customer->user_id)
            ->where('payment_status', 'delayed')
            ->count();

        $repaymentScore = match (true) {
            $repaymentDelays === 0 => 20,
            $repaymentDelays <= 2 => 15,
            default => 5
        };

        // Liquidity (placeholder)
        $liquidityScore = 10; // Static value for demo

        // Supplier ratings
        $supplierRating = Product::where('user_id', $customer->user_id)
            ->avg('rating') ?? 0;
        $supplierScore = min($supplierRating * 2, 10); // Convert 5-star to 10-point scale

        $compositeScore = $monthlyPOSScore + $businessAgeScore + $industryRiskScore +
            $obligationsScore + $repaymentScore + $liquidityScore + $supplierScore;

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
            'liquidityScore' => $liquidityScore,
            'supplierRating' => round($supplierRating, 2),
            'supplierScore' => round($supplierScore, 2),
            'compositeScore' => round($compositeScore, 2),
        ];
    }

    private function calculateOrderBase(Order $order): float
    {
        $items = map_product_details($order->product_details);
        $subTotal = $items->sum('total');
        $shipping = $order->shipping_cost ?? 0;
        $discount = $order->coupon_discount ?? 0;
        $tax = calculate_order_tax($order);

        return $subTotal + $tax + $shipping - $discount;
    }

    private function determineRiskLevel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Low',
            $score >= 50 => 'Medium',
            default => 'High'
        };
    }

    private function mapScoreComponents(array $creditScore): array
    {
        return [
            'POS Revenue' => $creditScore['monthlyPOSScore'],
            'Industry Risk' => $creditScore['industryRiskScore'],
            'Repayment' => $creditScore['repaymentScore'],
            'Business Age' => $creditScore['businessAgeScore'],
            'Obligations' => $creditScore['obligationsScore'],
            'Liquidity' => $creditScore['liquidityScore'],
            'Supplier Ratings' => $creditScore['supplierScore'],
        ];
    }

    private function getPaymentTimeline(int $userId): array
    {
        $monthlyData = Wallet::selectRaw("MONTH(created_at) as month, SUM(amount) as total")
            ->where('user_id', $userId)
            ->where('transaction_type', 'user_repayment')
            ->whereYear('created_at', now()->year)
            ->groupByRaw("MONTH(created_at)")
            ->pluck('total', 'month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $data = [];

        foreach (range(1, 12) as $month) {
            $data[] = round($monthlyData[$month] ?? 0, 2);
        }

        return [
            'categories' => $months,
            'data' => $data,
        ];
    }

    private function getRiskFactors(): array
    {
        // Placeholder - implement actual risk analysis
        return [
            ['label' => 'Industry Volatility', 'value' => 'High Risk', 'class' => 'text-red-600'],
            ['label' => 'Debt-to-Revenue Ratio', 'value' => '1.2:1', 'class' => 'text-yellow-600'],
        ];
    }

    private function getComplianceStatus(Customer $customer): array
    {
        $crData = is_array($customer->cr_data)
            ? $customer->cr_data
            : json_decode($customer->cr_data, true);

        $crStatus = $crData['status']['name'] ?? 'Unknown';

        $statusBadgeMap = [
            'approved' => 'badge-success',
            'pending' => 'badge-warning',
            'suspended' => 'badge-neutral',
            'blacklisted' => 'badge-danger',
        ];

        return [
            [
                'name' => 'KYC Verification',
                'status' => ucfirst($customer->status),
                'badge' => 'badge-sm badge-outline ' . ($statusBadgeMap[$customer->status] ?? 'badge-secondary')
            ],
            [
                'name' => 'SIMAH Integration',
                'status' => 'Pending',
                'badge' => 'badge-sm badge-outline badge-warning'
            ],
            [
                'name' => 'CR Validation',
                'status' => $crStatus,
                'badge' => 'badge-sm badge-outline ' . (($crStatus === 'Valid') ? 'badge-success' : 'badge-danger')
            ],
        ];
    }
}
