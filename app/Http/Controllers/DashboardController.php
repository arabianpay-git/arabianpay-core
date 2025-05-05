<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\RiskManagement;
use App\Models\SchedulePayment;
use App\Models\RefundRequest;
use App\Models\CustomerCreditLimit;
use App\Models\Product;
use App\Models\State;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $dateRange = $request->input('date_range', '12M');

        // 1. Loan Performance Charts
        $loanData = $this->getLoanPerformanceData($dateRange);

        // 2. Financial Health Charts
        $financialData = $this->getFinancialHealthData($dateRange);

        // 3. Risk Management Charts
        $riskData = $this->getRiskManagementData();

        // 4. Operational Metrics
        $operationalData = $this->getOperationalMetrics($dateRange);

        // 5. Category Wise Sales
        $categorySales = [];

        foreach (Order::all() as $order) {
            $details = json_decode($order->product_details, true);

            foreach ($details as $item) {
                $product = Product::with('category')->find($item['product_id']);
                if ($product && $product->category) {
                    $categoryName = $product->category->name;
                    $quantity = $item['quantity'] ?? 0;

                    if (!isset($categorySales[$categoryName])) {
                        $categorySales[$categoryName] = 0;
                    }

                    $categorySales[$categoryName] += $quantity;
                }
            }
        }

        // Format sales
        $categorySales = collect($categorySales)->map(function ($qty, $cat) {
            return ['name' => $cat, 'sales' => $qty];
        })->values();

        // 6. Category Wise Stock
        $categoryStock = Category::with('products')->get()->map(function ($category) {
            $stock = $category->products->sum('current_stock');
            return [
                'name' => $category->name,
                'stock' => $stock,
            ];
        });

        return view('admin.dashboard.index', compact(
            'loanData',
            'financialData',
            'riskData',
            'operationalData',
            'dateRange',
            'categorySales',
            'categoryStock',
        ));
    }


    private function getLoanPerformanceData($range)
    {
        return [
            'disbursement_vs_repayment' => Transaction::getLoanFlowData($range),
            'payment_status' => SchedulePayment::getPaymentStatusDistribution(),
            'overdue_instalments' => SchedulePayment::getOverdueTrend($range),
            'loan_portfolio' => Transaction::getRiskExposureData(),
            'active_loans' => Transaction::where('general_status', 'active')->count()
        ];
    }

    private function getFinancialHealthData($range)
    {
        return [
            'revenue_breakdown' => Order::getRevenueStreams($range),
            'wallet_balances' => Wallet::getBalanceTrends($range),
            'refund_analysis' => RefundRequest::getRefundAnalysis(),
            'cash_flow' => Transaction::getCashFlowData($range)
        ];
    }

    private function getRiskManagementData()
    {
        return [
            'risk_distribution' => RiskManagement::getRiskScoreDistribution(),
            'default_rates' => RiskManagement::getDefaultRatesByBusiness(),
            'credit_scores' => RiskManagement::getAverageCreditScores(),
            'credit_utilization' => CustomerCreditLimit::getCreditUtilization()
        ];
    }

    private function getOperationalMetrics($range)
    {
        return [
            'order_statuses' => Order::getStatusDistribution($range),
            'fulfillment_times' => Order::getFulfillmentTimes($range),
            'payment_methods' => Order::getPaymentMethodDistribution($range),
            'settlement_status' => Transaction::getSettlementStatus()
        ];
    }


    public function getStates($country_id)
    {
        return response()->json(State::where('country_id', $country_id)->get());
    }

    public function getCities($state_id)
    {
        return response()->json(City::where('state_id', $state_id)->get());
    }

    public function home()
    {

        return view('welcome', compact('cities'));
    }
}
