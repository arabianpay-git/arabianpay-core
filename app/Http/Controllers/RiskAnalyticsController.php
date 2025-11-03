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

        $baseQuery = User::whereIn('user_type', ['merchant', 'user'])
            ->with(['merchant.businessType', 'customer.businessType', 'transactions'])
            ->orderBy('created_at', 'desc');

        $weights = [
            'cr_id' => $request->input('weight_cr_id'),
            'pos' => $request->input('weight_pos'),
            'repayment' => $request->input('weight_repayment'),
            'industry' => $request->input('weight_industry'),
            'location' => $request->input('weight_location'),
        ];

        $weights = array_filter($weights, fn($weight) => !is_null($weight));

        if (!$search) {
            $usersPaginator = $baseQuery->paginate($perPage);

            $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers(
                $usersPaginator->getCollection()
            );

            if (!empty($businessNames)) {
                $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
            }

            $risksCollection = $this->riskAnalyticsService->calculateForUsers(
                $usersPaginator->getCollection(),
                $weights
            );

            $ordered = $risksCollection->values();

            $paginatedRisks = new LengthAwarePaginator(
                $ordered,
                $usersPaginator->total(),
                $usersPaginator->perPage(),
                $usersPaginator->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            );

            return view('admin.risk-management.score', ['risks' => $paginatedRisks]);
        }

        $candidateLimit = 2000;
        $candidates = $baseQuery->limit($candidateLimit)->get();

        $filtered = $candidates->filter(function ($user) use ($search) {
            $searchLower = strtolower($search);

            if (
                str_contains(strtolower($user->first_name ?? ''), $searchLower) ||
                str_contains(strtolower($user->last_name ?? ''), $searchLower) ||
                str_contains(strtolower($user->email ?? ''), $searchLower) ||
                str_contains(strtolower($user->phone_number ?? ''), $searchLower) ||
                str_contains(strtolower($user->iqama ?? ''), $searchLower)
            ) {
                return true;
            }

            if ($user->merchant && str_contains(strtolower($user->merchant->business_name ?? ''), $searchLower)) {
                return true;
            }

            if ($user->customer && str_contains(strtolower($user->customer->business_name ?? ''), $searchLower)) {
                return true;
            }

            return false;
        });

        if ($filtered->isEmpty()) {
            $paginatedRisks = new LengthAwarePaginator(collect(), 0, $perPage, 1, [
                'path' => request()->url(),
                'query' => request()->query()
            ]);

            return view('admin.risk-management.score', ['risks' => $paginatedRisks]);
        }

        $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers($filtered);

        if (!empty($businessNames)) {
            $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
        }

        $risksAll = $this->riskAnalyticsService->calculateForUsers($filtered, $weights);

        $orderedAll = $risksAll->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $orderedAll->count();
        $itemsForCurrentPage = $orderedAll->forPage($page, $perPage)->values();

        $paginatedRisks = new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $page,
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

        $riskScore = RiskScore::updateOrCreate(
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
