<?php

namespace App\Http\Controllers;

use App\Models\RiskScore;
use App\Models\User;
use App\Services\RiskAnalyticsService;
use App\Services\RiskDashboardService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RiskAnalyticsController extends Controller
{
    protected $riskDashboardService;
    protected $riskAnalyticsService;

    public function __construct(
        RiskDashboardService $riskDashboardService,
        RiskAnalyticsService $riskAnalyticsService
    ) {
        $this->riskDashboardService = $riskDashboardService;
        $this->riskAnalyticsService = $riskAnalyticsService;
    }

    public function dashboard(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ];

        $dashboardData = $this->riskDashboardService->getDashboardData($filters);

        return view('admin.risk-management.dashboard', [
            'portfolioData' => $dashboardData['portfolio'],
            'pipelineData' => $dashboardData['pipeline'],
            'ewsData' => $dashboardData['ews'],
            'riskScores' => $dashboardData['risk_scores'],
            'activeAlerts' => $dashboardData['alerts'],
            'filters' => $filters
        ]);
    }

    public function score(Request $request)
    {
        $search = trim($request->input('search', ''));
        $order = $request->input('order', 'desc');
        $perPage = 10;

        $typeParam = strtolower($request->input('type', ''));
        $allowedTypes = ['merchant', 'user'];
        $userTypes = in_array($typeParam, $allowedTypes) ? [$typeParam] : $allowedTypes;

        $weights = array_filter([
            'cr_id' => $request->input('weight_cr_id'),
            'pos' => $request->input('weight_pos'),
            'repayment' => $request->input('weight_repayment'),
            'industry' => $request->input('weight_industry'),
            'location' => $request->input('weight_location'),
        ], fn($weight) => !is_null($weight));

        $baseQuery = User::query()
            ->whereIn('user_type', $userTypes)
            ->where(function ($query) {
                $query->whereHas('merchant')
                    ->orWhereHas('customer');
            })
            ->with(['merchant.businessType', 'customer.businessType', 'transactions'])
            ->orderBy('created_at', $order);

        if ($search) {
            $searchLower = strtolower($search);

            $baseQuery->where(function ($query) use ($searchLower) {
                $query->where('first_name', 'like', "%{$searchLower}%")
                    ->orWhere('last_name', 'like', "%{$searchLower}%")
                    ->orWhere('email', 'like', "%{$searchLower}%")
                    ->orWhere('phone_number', 'like', "%{$searchLower}%")
                    ->orWhere('iqama', 'like', "%{$searchLower}%");

                $query->orWhereHas('merchant', function ($q) use ($searchLower) {
                    $q->where('business_name', 'like', "%{$searchLower}%");
                });

                $query->orWhereHas('customer', function ($q) use ($searchLower) {
                    $q->where('business_name', 'like', "%{$searchLower}%");
                });
            });
        }

        $usersPaginator = $baseQuery->paginate($perPage);
        $usersCollection = $usersPaginator->getCollection();

        if ($usersCollection->isEmpty()) {
            return view('admin.risk-management.score', [
                'risks' => new LengthAwarePaginator(collect(), 0, $perPage, 1, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ])
            ]);
        }

        $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers($usersCollection);
        if (!empty($businessNames)) {
            $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
        }

        $risksCollection = $this->riskAnalyticsService->calculateForUsers(
            $usersCollection,
            $weights
        );

        $paginatedRisks = new LengthAwarePaginator(
            $risksCollection->values(),
            $usersPaginator->total(),
            $usersPaginator->perPage(),
            $usersPaginator->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.risk-management.score', ['risks' => $paginatedRisks]);
    }

    public function scoreUpdate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'risk_score' => 'required|numeric|min:0|max:100',
            'reason' => 'required|string',
        ]);

        RiskScore::updateOrCreate(
            ['user_id' => $request->user_id],
            [
                'risk_score' => $request->risk_score,
                'reason' => $request->reason,
            ]
        );

        session()->forget('otp_verified');

        return redirect()->back()->with('success', 'Risk score updated successfully.');
    }
}
