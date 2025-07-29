<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SchedulePayment;
use App\Models\User;
use App\Models\UserSearch;
use App\Services\CreditAssessmentService;
use App\Services\PortfolioPerformanceService;
use App\Services\RiskAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReportController extends Controller
{
    public function productStock()
    {
        $products = Product::select('id', 'name', 'thumbnail', 'brand_id', 'unit_price', 'current_stock', 'sku', 'published')
            ->with('brand:id,name')
            ->latest()
            ->paginate(10);

        return view('admin.reports.stock', compact('products'));
    }

    public function productWishlist()
    {
        $products = Product::select('id', 'name', 'thumbnail', 'brand_id')
            ->with('brand:id,name')
            ->withCount('wishlists')
            ->latest()
            ->paginate(10);

        return view('admin.reports.wishlist', compact('products'));
    }

    public function userSearch()
    {
        $userSearches = UserSearch::with(['user:id,first_name,last_name,business_name'])
            ->latest()
            ->paginate(10);

        return view('admin.reports.searches', compact('userSearches'));
    }

    public function index()
    {
        return view('google-reviews');
    }

    public function getReviews(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
        ]);

        $apiKey = 'AIzaSyC0Oe6-EvwCkjpbSXt-CyDNi8QS3yPrrC0';

        // Step 1: Get Place ID
        $searchResponse = Http::get('https://maps.googleapis.com/maps/api/place/findplacefromtext/json', [
            'input' => $request->business_name,
            'inputtype' => 'textquery',
            'fields' => 'place_id',
            'key' => $apiKey,
        ]);

        $placeId = $searchResponse['candidates'][0]['place_id'] ?? null;

        if (!$placeId) {
            return back()->with('error', 'Business not found.');
        }

        // Step 2: Get Reviews
        $detailsResponse = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
            'place_id' => $placeId,
            'fields' => 'name,reviews,rating,user_ratings_total',
            'key' => $apiKey,
        ]);

        return view('google-reviews', [
            'reviews' => $detailsResponse['result']['reviews'] ?? [],
            'business' => $detailsResponse['result']['name'] ?? $request->business_name,
            'overallRating' => $detailsResponse['result']['rating'] ?? null,
            'totalReviews' => $detailsResponse['result']['user_ratings_total'] ?? 0,
        ]);
    }









    public function portfolioPerformanceReport(Request $request, PortfolioPerformanceService $service)
    {
        $filters = $request->only(['from', 'to', 'merchant_id']);
        $reports = $service->getReport($filters);
        return view('admin.reports.portfolio_performance_report', compact('reports'));
    }

    public function merchantCreditHistoryReport(CreditAssessmentService $creditAssessmentService, RiskAnalyticsService $riskAnalyticsService)
    {
        $customers = Customer::with('user')->paginate(10);

        foreach ($customers as $customer) {
            try {
                $user = $customer->user;

                // Use services as in CreditManagmentController
                $creditScoreService = $creditAssessmentService->assess($customer->user_id);
                $riskScoreService = $riskAnalyticsService->calculateForUser($customer->user);

                $creditScore = $creditScoreService['creditScore']['compositeScore'] ?? 0;
                $riskScore = $riskScoreService->total_score ?? 0;

                $oldCreditLimit = 20000; // Static or from DB if dynamic

                $finalScore = $creditScore * ($riskScore / 100);
                $newCreditLimit = $oldCreditLimit * ($finalScore / 100);

                $orders = $user->orders()->where('delivery_status', 'delivered')->get();
                $utilizedAmount = $this->calculateTotalOrderAmount($orders);

                $repaymentRate = $this->calculateRepaymentRate($user->id);

                $customer->first_name = $user->first_name;
                $customer->last_name = $user->last_name;
                $customer->credit_limit = $newCreditLimit;
                $customer->utilized_amount = $utilizedAmount;
                $customer->repayment_rate = round($repaymentRate, 2);
                $customer->score_change = $finalScore - 100;
            } catch (\Throwable $e) {
                report($e);
                $customer->first_name = '-';
                $customer->last_name = '-';
                $customer->credit_limit = 0;
                $customer->utilized_amount = 0;
                $customer->repayment_rate = 0;
                $customer->score_change = 0;
            }
        }

        return view('admin.reports.merchant_credit_history_report', compact('customers'));
    }

    private function calculateTotalOrderAmount($orders): float
    {
        $total = 0;

        foreach ($orders as $order) {
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

            $total += $base + $commissionAmount + $commissionTaxAmt;
        }

        return $total;
    }

    private function calculateRepaymentRate($userId): float
    {
        $totalPayments = SchedulePayment::where('user_id', $userId)->count();
        $paidPayments = SchedulePayment::where('user_id', $userId)->where('payment_status', 'paid')->count();

        return $totalPayments > 0 ? ($paidPayments / $totalPayments) * 100 : 0;
    }


    public function supplierTransactionReport()
    {
        return view('admin.reports.supplier_transaction_report');
    }

    public function instalmentRepaymentReport()
    {
        return view('admin.reports.instalment_repayment_report');
    }

    public function riskExposureAnalysis()
    {
        return view('admin.reports.risk_exposure_analysis');
    }

    public function amlActivityReport()
    {
        return view('admin.reports.aml_activity_report');
    }

    public function collectionEfficiencyReport()
    {
        return view('admin.reports.collection_efficiency_report');
    }

    public function onboardingFunnelReport()
    {
        return view('admin.reports.onboarding_funnel_report');
    }

    public function regulatoryComplianceReport()
    {
        return view('admin.reports.regulatory_compliance_report');
    }

    public function systemActivityAuditReport()
    {
        return view('admin.reports.system_activity_audit_report');
    }

    public function financialSummaryReport()
    {
        return view('admin.reports.financial_summary_report');
    }

    public function delinquencyAgingReport()
    {
        return view('admin.reports.delinquency_aging_report');
    }

    public function productSkuPerformanceReport()
    {
        return view('admin.reports.product_sku_performance_report');
    }

    public function supportTicketResolutionReport()
    {
        return view('admin.reports.support_ticket_resolution_report');
    }

    public function campaignEffectivenessReport()
    {
        return view('admin.reports.campaign_effectiveness_report');
    }
}
