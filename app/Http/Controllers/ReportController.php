<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Product;
use App\Models\RefundRequest;
use App\Models\SchedulePayment;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSearch;
use App\Models\Wallet;
use App\Services\CreditAssessmentService;
use App\Services\PortfolioPerformanceService;
use App\Services\RiskAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;

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

    public function merchantCreditHistoryReport(Request $request, CreditAssessmentService $creditAssessmentService, RiskAnalyticsService $riskAnalyticsService)
    {
        $query = Customer::with('user');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => '"To Date" must be equal or after "From Date".',
        ]);

        if ($request->filled('from')) {
            $query->whereHas('user', fn($q) => $q->whereDate('created_at', '>=', $request->from));
        }

        if ($request->filled('to')) {
            $query->whereHas('user', fn($q) => $q->whereDate('created_at', '<=', $request->to));
        }

        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->customer_id);
        }

        $customers = $query->paginate(10)->appends($request->all());

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

    public function supplierTransactionReport(Request $request)
    {
        $query = Merchant::with('user');

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ], [
            'to.after_or_equal' => '"To Date" must be equal or after "From Date".',
        ]);

        if ($request->filled('from')) {
            $query->whereHas('user', fn($q) => $q->whereDate('created_at', '>=', $request->from));
        }

        if ($request->filled('to')) {
            $query->whereHas('user', fn($q) => $q->whereDate('created_at', '<=', $request->to));
        }

        if ($request->filled('merchant_id')) {
            $query->where('user_id', $request->merchant_id);
        }

        $merchants = $query->paginate(10)->appends($request->all());

        // Group orders by seller_id
        $orders = Order::whereNotNull('seller_id')->get()->groupBy('seller_id');

        $supplierData = $merchants->map(function ($merchant) use ($orders) {
            // Get orders for this merchant (user_id used as seller_id in orders)
            $merchantOrders = $orders->get($merchant->user_id, collect());

            // Calculate total order volume
            $totalOrderAmount = $merchantOrders->sum(function ($order) {
                if (!$order->product_details) {
                    return 0;
                }

                return map_product_details($order->product_details)->sum('total');
            });

            // Get wallet transactions for payouts
            $walletTransactions = Wallet::where('seller_id', $merchant->user_id)
                ->where('transaction_type', 'seller_payment')
                ->get();

            $totalPayouts = $walletTransactions->sum('amount');

            // Fulfillment percentage based on delivery_status = 'delivered'
            $fulfilledCount = $merchantOrders->where('delivery_status', 'delivered')->count();

            $fulfilledPercentage = $merchantOrders->count() > 0
                ? round(($fulfilledCount / $merchantOrders->count()) * 100, 2)
                : 0;

            $disputeCount = $merchantOrders->where('delivery_status', 'delivered')->count();

            $disputePercentage = $merchantOrders->count() > 0
                ? round(($disputeCount / $merchantOrders->count()) * 100, 2)
                : 0;

            return (object)[
                'id'                    => $merchant->id,
                'user'                  => $merchant->user,
                'order_volume'          => round($totalOrderAmount, 2),
                'fulfillment_count'     => $fulfilledCount,
                'fulfillment_percentage' => $fulfilledPercentage,
                'total_payouts'         => round($totalPayouts, 2),
                'dispute_count'         => $disputeCount,
                'dispute_percentage'    => $disputePercentage,
            ];
        });

        // Manual pagination
        $page = $request->get('page', 1);
        $perPage = 10;
        $suppliers = new LengthAwarePaginator(
            $supplierData->forPage($page, $perPage)->values(),
            $supplierData->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.reports.supplier_transaction_report', compact('suppliers'));
    }

    public function instalmentRepaymentReport(Request $request)
    {
        $query = SchedulePayment::query();

        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', $request->input('to'));
        }

        if ($request->filled('customer_id')) {
            $query->where('user_id', $request->input('customer_id'));
        }

        $query->orderBy('due_date', 'desc');

        $instalments = $query->paginate(10)->withQueryString();

        // Pass instalments and filters to view
        return view('admin.reports.instalment_repayment_report', [
            'instalments' => $instalments,
        ]);
    }


    public function riskExposureAnalysis(Request $request)
    {
        $dateRange = $request->input('date_range', '12M');

        $loanData = $this->getLoanPerformanceData($dateRange);

        $reports = [
            'total_exposure_percentage' => 75.5,
            'average_score' => 82.3,
            'delinquency_percentage' => $loanData['overdue_instalments']['rate'] ?? 0,
            'high_risk_count' => 15,
            'exposure_percentages' => [40, 70, 85, 90, 75],
            'segments' => ['Segment A', 'Segment B', 'Segment C', 'Segment D', 'Segment E'],
        ];

        return view('admin.reports.risk_exposure_analysis', compact('reports'));
    }

    private function getLoanPerformanceData($range)
    {
        return [
            'overdue_instalments' => SchedulePayment::getOverdueTrend($range),
        ];
    }


    public function amlActivityReport(Request $request)
    {
        $allActivities = collect([
            (object)[
                'alert_id' => 'ALERT001',
                'type' => 'Transaction Monitoring',
                'screening_status' => 'Flagged',
                'sar_status' => 'Submitted',
                'submission_date' => now()->subDays(3)->toDateString(),
            ],
            (object)[
                'alert_id' => 'ALERT002',
                'type' => 'Customer Screening',
                'screening_status' => 'Reviewed',
                'sar_status' => 'Pending',
                'submission_date' => now()->subDays(10)->toDateString(),
            ],
            (object)[
                'alert_id' => 'ALERT003',
                'type' => 'Sanctions Check',
                'screening_status' => 'Cleared',
                'sar_status' => 'N/A',
                'submission_date' => now()->subDays(15)->toDateString(),
            ],
            // You can add more dummy records here to test pagination
            (object)[
                'alert_id' => 'ALERT004',
                'type' => 'Transaction Monitoring',
                'screening_status' => 'Flagged',
                'sar_status' => 'Pending',
                'submission_date' => now()->subDays(5)->toDateString(),
            ],
            (object)[
                'alert_id' => 'ALERT005',
                'type' => 'Customer Screening',
                'screening_status' => 'Flagged',
                'sar_status' => 'Submitted',
                'submission_date' => now()->subDays(7)->toDateString(),
            ],
        ]);

        // Get current page from the request, default is 1
        $currentPage = Paginator::resolveCurrentPage();

        // Define how many items per page
        $perPage = 10;

        // Slice the collection to get items to display in current page
        $currentPageItems = $allActivities->slice(($currentPage - 1) * $perPage, $perPage)->values();

        // Create paginator instance
        $paginatedActivities = new LengthAwarePaginator(
            $currentPageItems,
            $allActivities->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('admin.reports.aml_activity_report', ['amlActivities' => $paginatedActivities]);
    }

    public function collectionEfficiencyReport()
    {
        // Create dummy collection data
        $data = collect([
            (object)[
                'collector_id' => 'COLL001',
                'recovery_rate' => 85.45,
                'merchant_list' => ['Merchant A', 'Merchant B', 'Merchant C'],
                'amount_recovered' => 150000.75,
                'promises_kept' => 12,
            ],
            (object)[
                'collector_id' => 'COLL002',
                'recovery_rate' => 78.32,
                'merchant_list' => ['Merchant D', 'Merchant E'],
                'amount_recovered' => 98000.50,
                'promises_kept' => 9,
            ],
            (object)[
                'collector_id' => 'COLL003',
                'recovery_rate' => 92.15,
                'merchant_list' => ['Merchant F'],
                'amount_recovered' => 120000.00,
                'promises_kept' => 15,
            ],
            // Add more dummy items as needed
        ]);

        // Paginate manually (page 1, 10 per page)
        $perPage = 10;
        $page = request()->get('page', 1);
        $items = $data->slice(($page - 1) * $perPage, $perPage)->values();
        $paginatedData = new LengthAwarePaginator($items, $data->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return view('admin.reports.collection_efficiency_report', ['collectionData' => $paginatedData]);
    }

    public function onboardingFunnelReport(Request $request)
    {
        $range = $request->input('date_range', '12M');

        $monthsBack = match ($range) {
            '1M' => 1,
            '3M' => 3,
            '6M' => 6,
            default => 12,
        };

        $months = collect(range(0, $monthsBack - 1))->map(function ($i) {
            return Carbon::now()->subMonths($i)->format('Y-m');
        })->reverse();

        $funnelData = $months->map(function ($month) {
            $start = Carbon::parse($month . '-01')->startOfMonth();
            $end = Carbon::parse($month . '-01')->endOfMonth();

            $applications_submitted = Merchant::whereBetween('created_at', [$start, $end])->count();
            $verified = Merchant::whereBetween('created_at', [$start, $end])->where('status', 'active')->count();
            $approved = Merchant::whereBetween('created_at', [$start, $end])->where('status', 'approved')->count();
            $kyc_passed = Merchant::whereBetween('created_at', [$start, $end])->where('status', 'contract_sent')->count();

            $conversion_rate = $applications_submitted > 0
                ? round(($kyc_passed / $applications_submitted) * 100, 2)
                : 0.00;

            return [
                'month' => $month,
                'applications_submitted' => $applications_submitted,
                'verified' => $verified,
                'approved' => $approved,
                'kyc_passed' => $kyc_passed,
                'conversion_rate' => $conversion_rate,
            ];
        });

        if ($request->ajax()) {
            return response()->json($funnelData->values());
        }

        return view('admin.reports.onboarding_funnel_report', [
            'funnelData' => $funnelData->values()->all()  // <<< Make sure it's a plain array here
        ]);
    }

    public function regulatoryComplianceReport()
    {
        $queryMerchants = Merchant::with('user');
        $queryCustomers = Customer::with('user');

        // Apply filters from request
        $from = request('from');
        $to = request('to');
        $merchantId = request('merchant_id');

        // Filter merchants by merchant_id if provided
        if ($merchantId) {
            $queryMerchants->where('user_id', $merchantId);
            $queryCustomers->where('user_id', $merchantId);
        }

        // Filter by created_at or any relevant date column (adjust as per your model)
        if ($from) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $queryMerchants->where('created_at', '>=', $fromDate);
            $queryCustomers->where('created_at', '>=', $fromDate);
        }
        if ($to) {
            $toDate = Carbon::parse($to)->endOfDay();
            $queryMerchants->where('created_at', '<=', $toDate);
            $queryCustomers->where('created_at', '<=', $toDate);
        }

        $merchantFiles = $queryMerchants->get()->map(function ($merchant) {
            $userName = trim(($merchant->user->first_name ?? '') . ' ' . ($merchant->user->last_name ?? ''));
            if (!$userName) {
                $userName = 'Unnamed Merchant';
            }

            return [
                'name' => $userName,
                'type' => 'Merchant',
                'files' => [
                    'ID Document' => [
                        'status' => $merchant->id_document ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->id_document,
                    ],
                    'CR Certificate' => [
                        'status' => $merchant->cr_certificate ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->cr_certificate,
                    ],
                    'VAT Certificate' => [
                        'status' => $merchant->vat_certificate ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->vat_certificate,
                    ],
                    'Registration Form' => [
                        'status' => $merchant->registration_form ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->registration_form,
                    ],
                    'VAT Register File' => [
                        'status' => $merchant->vat_register_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->vat_register_file,
                    ],
                    'Return Policy File' => [
                        'status' => $merchant->return_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->return_policy_file,
                    ],
                    'Delivery Policy File' => [
                        'status' => $merchant->delivery_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->delivery_policy_file,
                    ],
                    'Cancel Policy File' => [
                        'status' => $merchant->cancel_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->cancel_policy_file,
                    ],
                    'Owner Iqama Image' => [
                        'status' => $merchant->owner_iqama_image ? 'Uploaded' : 'Not uploaded',
                        'path' => $merchant->owner_iqama_image,
                    ],
                ],
            ];
        });

        $customerFiles = $queryCustomers->get()->map(function ($customer) {
            $userName = trim(($customer->user->first_name ?? '') . ' ' . ($customer->user->last_name ?? ''));
            if (!$userName) {
                $userName = 'Unnamed Customer';
            }

            return [
                'name' => $userName,
                'type' => 'Customer',
                'files' => [
                    'ID Document' => [
                        'status' => $customer->id_document ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->id_document,
                    ],
                    'CR Certificate' => [
                        'status' => $customer->cr_certificate ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->cr_certificate,
                    ],
                    'VAT Certificate' => [
                        'status' => $customer->vat_certificate ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->vat_certificate,
                    ],
                    'Registration Form' => [
                        'status' => $customer->registration_form ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->registration_form,
                    ],
                    'VAT Register File' => [
                        'status' => $customer->vat_register_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->vat_register_file,
                    ],
                    'Return Policy File' => [
                        'status' => $customer->return_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->return_policy_file,
                    ],
                    'Delivery Policy File' => [
                        'status' => $customer->delivery_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->delivery_policy_file,
                    ],
                    'Cancel Policy File' => [
                        'status' => $customer->cancel_policy_file ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->cancel_policy_file,
                    ],
                    'Owner Iqama Image' => [
                        'status' => $customer->owner_iqama_image ? 'Uploaded' : 'Not uploaded',
                        'path' => $customer->owner_iqama_image,
                    ],
                ],
            ];
        });

        // Merge collections
        $merged = $merchantFiles->merge($customerFiles);

        // Pagination parameters
        $perPage = 10;
        $page = request()->get('page', 1);
        $total = $merged->count();

        // Slice for current page
        $itemsForCurrentPage = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        // Create paginator
        $complianceFiles = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('admin.reports.regulatory_compliance_report', compact('complianceFiles'));
    }

    public function systemActivityAuditReport(Request $request)
    {
        $search = $request->input('search');
        $order = $request->input('order', 'desc');

        $logs = Activity::query()
            ->with(['causer', 'subject'])
            ->when($request->input('start_date'), function ($query) use ($request) {
                return $query->where('created_at', '>=', $request->input('start_date'));
            })
            ->when($request->input('end_date'), function ($query) use ($request) {
                return $query->where('created_at', '<=', $request->input('end_date'));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhereHas('causer', function ($q2) use ($search) {
                            $q2->where('log_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subject', function ($q3) use ($search) {
                            $q3->where('log_name', 'like', "%{$search}%");
                        });
                });
            });

        // Ordering the logs
        $logs = $logs->orderBy('created_at', $order)
            ->paginate(10)
            ->appends(['search' => $search, 'order' => $order]);

        return view('admin.reports.system_activity_audit_report', compact('logs'));
    }

    public function financialSummaryReport(Request $request)
    {
        $fromDate = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $toDate = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $merchantId = $request->input('merchant_id');

        $query = Transaction::query();

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }
        if ($merchantId) {
            $query->where('seller_id', $merchantId);
        }

        $totalRevenue = (clone $query)->sum('collected');
        $supplierPayouts = (clone $query)->sum('retrieved');

        // Operational costs = 1.5% of total revenue (collected)
        $operationalCosts = $totalRevenue * 0.015;

        $netProfit = $totalRevenue - $supplierPayouts - $operationalCosts;

        $monthsCount = 6;
        $endDate = $toDate ?? now();
        $startDate = $fromDate ?? now()->copy()->subMonths($monthsCount - 1)->startOfMonth();

        $monthlyLabels = [];
        $monthlyRevenue = [];

        for ($i = 0; $i < $monthsCount; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthlyLabels[] = $month->format('M Y');

            $monthQuery = Transaction::query();

            if ($fromDate) {
                $monthQuery->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $monthQuery->where('created_at', '<=', $toDate);
            }
            if ($merchantId) {
                $monthQuery->where('seller_id', $merchantId);
            }

            $monthQuery->whereBetween('created_at', [$month->startOfMonth(), $month->endOfMonth()]);

            $monthlyRevenue[] = round($monthQuery->sum('collected'), 2);
        }

        $summary = [
            'total_revenue' => round($totalRevenue, 2),
            'supplier_payouts' => round($supplierPayouts, 2),
            'operational_costs' => round($operationalCosts, 2),
            'net_profit' => round($netProfit, 2),
        ];

        $charts = [
            'months' => $monthlyLabels,
            'monthly_revenue' => $monthlyRevenue,
        ];

        return view('admin.reports.financial_summary_report', compact('summary', 'charts'));
    }

    public function delinquencyAgingReport(Request $request)
    {
        $fromDate = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $toDate = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $merchantId = $request->input('merchant_id');
        $today = now()->startOfDay();

        $query = SchedulePayment::query()
            ->where('payment_status', '!=', 'paid'); // unpaid/outstanding

        if ($fromDate) {
            $query->where('due_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('due_date', '<=', $toDate);
        }
        if ($merchantId) {
            $query->where('seller_id', $merchantId);
        }

        $instalments = $query->select('seller_id', 'due_date', 'instalment_amount')->get();

        $delinquencyData = [];

        foreach ($instalments as $instalment) {
            $merchantId = $instalment->seller_id;
            $dpd = $today->diffInDays($instalment->due_date, false);

            if ($dpd > 0) {
                if (!isset($delinquencyData[$merchantId])) {
                    $delinquencyData[$merchantId] = [
                        'dpd_1_15' => 0,
                        'dpd_16_30' => 0,
                        'dpd_31_60' => 0,
                        'dpd_over_60' => 0,
                        'total_outstanding' => 0,
                    ];
                }

                if ($dpd >= 1 && $dpd <= 15) {
                    $delinquencyData[$merchantId]['dpd_1_15'] += $instalment->instalment_amount;
                } elseif ($dpd >= 16 && $dpd <= 30) {
                    $delinquencyData[$merchantId]['dpd_16_30'] += $instalment->instalment_amount;
                } elseif ($dpd >= 31 && $dpd <= 60) {
                    $delinquencyData[$merchantId]['dpd_31_60'] += $instalment->instalment_amount;
                } elseif ($dpd > 60) {
                    $delinquencyData[$merchantId]['dpd_over_60'] += $instalment->instalment_amount;
                }

                $delinquencyData[$merchantId]['total_outstanding'] += $instalment->instalment_amount;
            }
        }

        // Load merchant info for names
        $merchantIds = array_keys($delinquencyData);
        $merchants = User::whereIn('id', $merchantIds)
            ->select('id', 'first_name', 'last_name', 'business_name')
            ->get()
            ->keyBy('id');

        return view('admin.reports.delinquency_aging_report', compact('delinquencyData', 'merchants'));
    }

    public function productSkuPerformanceReport(Request $request)
    {
        $fromDate = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $toDate = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $merchantId = $request->input('merchant_id');

        // Fetch filtered orders
        $ordersQuery = Order::query();

        if ($fromDate) {
            $ordersQuery->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $ordersQuery->where('created_at', '<=', $toDate);
        }
        if ($merchantId) {
            $ordersQuery->where('seller_id', $merchantId);
        }

        $orders = $ordersQuery->get();

        // Aggregate product sales data
        $productSales = [];

        foreach ($orders as $order) {
            // Assuming product_details is JSON array with each item having product_id, quantity
            $products = json_decode($order->product_details, true) ?? [];

            foreach ($products as $prod) {
                $productId = $prod['product_id'] ?? null;
                $quantity = $prod['quantity'] ?? 0;

                if (!$productId) {
                    continue;
                }

                if (!isset($productSales[$productId])) {
                    $productSales[$productId] = [
                        'sales_volume' => 0,
                        'total_days_to_sell' => 0,
                        'order_count' => 0,
                        'merchant_ids' => [],
                    ];
                }

                $productSales[$productId]['sales_volume'] += $quantity;
                $productSales[$productId]['total_days_to_sell'] += $order->created_at->diffInDays(now());
                $productSales[$productId]['order_count'] += 1;
                $productSales[$productId]['merchant_ids'][$order->seller_id] = true;
            }
        }

        // Calculate return counts per product_id from RefundRequest similarly
        $refundsQuery = RefundRequest::query();

        if ($fromDate) {
            $refundsQuery->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $refundsQuery->where('created_at', '<=', $toDate);
        }
        if ($merchantId) {
            $refundsQuery->where('seller_id', $merchantId);
        }

        $refunds = $refundsQuery->get();

        $returnCounts = [];

        foreach ($refunds as $refund) {
            $products = json_decode($refund->order->product_details ?? '[]', true);

            foreach ($products as $prod) {
                $productId = $prod['product_id'] ?? null;
                if (!$productId) continue;

                $returnCounts[$productId] = ($returnCounts[$productId] ?? 0) + 1;
            }
        }

        // Prepare final product performance array
        $skuPerformanceData = [];

        foreach ($productSales as $productId => $data) {
            $salesVolume = $data['sales_volume'];
            $avgTimeToSell = $data['order_count'] > 0 ? round($data['total_days_to_sell'] / $data['order_count'], 2) : 0;
            $returnCount = $returnCounts[$productId] ?? 0;
            $returnRate = $salesVolume > 0 ? round(($returnCount / $salesVolume) * 100, 2) : 0;
            $merchantCoverage = count($data['merchant_ids']);

            $skuPerformanceData[] = [
                'product_id' => $productId,
                'sales_volume' => $salesVolume,
                'avg_time_to_sell' => $avgTimeToSell,
                'return_rate' => $returnRate,
                'merchant_coverage' => $merchantCoverage,
            ];
        }

        // Fetch merchants for filter dropdown
        $merchants = User::where('user_type', 'merchant')
            ->select('id', 'first_name', 'last_name', 'business_name')
            ->get();

        return view('admin.reports.product_sku_performance_report', compact('skuPerformanceData', 'merchants'));
    }

    public function supportTicketResolutionReport(Request $request)
    {
        $fromDate = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $toDate = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;
        $statusFilter = $request->input('status'); // 'active', 'solved', 'draft', 'canceled'
        $userId = $request->input('user_id');     // filter by ticket creator
        $merchantId = $request->input('merchant_id'); // filter by assigned_to (merchant)

        $query = SupportTicket::query();

        // Filter by user_id (ticket creator)
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by assigned_to (merchant)
        if ($merchantId) {
            $query->where('assigned_to', $merchantId);
        }

        // Filter by open date (created_at)
        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        // Filter by status enum
        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $slaDays = 3;

        $tickets = $query->get()->map(function ($ticket) use ($slaDays) {
            $openDate = $ticket->created_at;
            $resolutionDate = in_array($ticket->status, ['solved', 'canceled']) ? $ticket->updated_at : null;

            $slaBreached = false;
            if ($resolutionDate) {
                $daysToResolve = $openDate->diffInDays($resolutionDate);
                $slaBreached = $daysToResolve > $slaDays;
            }

            $feedbackScore = $ticket->feedback_score ?? '-';

            return (object)[
                'user_id' => $ticket->user_id,
                'first_name' => optional($ticket->user)->first_name,
                'last_name' => optional($ticket->user)->last_name,
                'ticket_id' => $ticket->ticket_number,
                'issue_type' => $ticket->subject,
                'open_date' => $openDate,
                'resolution_date' => $resolutionDate,
                'sla_breached' => $slaBreached,
                'feedback_score' => $feedbackScore,
                'status' => $ticket->status,
            ];
        });


        // For filter dropdowns: users (ticket creators) and merchants (assigned_to)
        $users = User::select('id', 'first_name', 'last_name')->get();
        $merchants = User::where('user_type', 'merchant')->select('id', 'first_name', 'last_name', 'business_name')->get();

        // Pass available statuses for filtering dropdown
        $statuses = ['active', 'solved', 'draft', 'canceled'];

        return view('admin.reports.support_ticket_resolution_report', compact('tickets', 'users', 'merchants', 'statuses'));
    }

    public function campaignEffectivenessReport()
    {
        $campaigns = collect([
            (object)[
                'campaign_id' => 'CMP-1001',
                'target_segment' => 'New Merchants',
                'offers_accepted_percentage' => 65.2,
                'incremental_orders_percentage' => 20.5,
                'roi' => 140.8,
            ],
            (object)[
                'campaign_id' => 'CMP-1002',
                'target_segment' => 'Top Performers',
                'offers_accepted_percentage' => 82.3,
                'incremental_orders_percentage' => 34.1,
                'roi' => 185.4,
            ],
            (object)[
                'campaign_id' => 'CMP-1003',
                'target_segment' => 'Low Volume Sellers',
                'offers_accepted_percentage' => 41.6,
                'incremental_orders_percentage' => 12.9,
                'roi' => 89.7,
            ],
        ]);

        return view('admin.reports.campaign_effectiveness_report', compact('campaigns'));
    }
}
