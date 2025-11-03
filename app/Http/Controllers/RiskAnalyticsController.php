<?php

namespace App\Http\Controllers;

use App\Models\RiskScore;
use App\Models\User;
use App\Services\RiskAnalyticsService;
use App\Services\RiskDashboardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * Dashboard - still uses RiskDashboardService (unchanged)
     */
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

        // Base query with relations, ORDER LATEST USER FIRST
        $baseQuery = User::whereIn('user_type', ['merchant', 'user'])
            ->with(['merchant.businessType', 'customer.businessType', 'transactions'])
            ->orderBy('created_at', 'desc'); // <- changed: latest users first

        // Optional weights from request
        $weights = [
            'cr_id' => $request->input('weight_cr_id'),
            'pos' => $request->input('weight_pos'),
            'repayment' => $request->input('weight_repayment'),
            'industry' => $request->input('weight_industry'),
            'location' => $request->input('weight_location'),
        ];

        // Filter out null weights
        $weights = array_filter($weights, fn($weight) => !is_null($weight));

        // --- NO SEARCH: Use DB pagination directly ---
        if (!$search) {
            // usersPaginator already ordered by created_at desc
            $usersPaginator = $baseQuery->paginate($perPage);

            // Extract business names from the current page users for Google prefetching
            $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers(
                $usersPaginator->getCollection()
            );

            if (!empty($businessNames)) {
                $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
            }

            // Calculate risks (will use cached ratings where available)
            // calculateForUsers will load per-user weights for each user
            $risksCollection = $this->riskAnalyticsService->calculateForUsers(
                $usersPaginator->getCollection(),
                $weights
            );

            // DO NOT re-sort by score — keep the latest-user-first order coming from the DB
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

        // --- SEARCH CASE: filter limited candidates ---
        $candidateLimit = 2000;
        // Ensure search candidates are fetched in latest-first order
        $candidates = $baseQuery->limit($candidateLimit)->get();

        // Apply search filter (filter preserves order)
        $filtered = $candidates->filter(function ($user) use ($search) {
            $searchLower = strtolower($search);

            // Check user fields
            if (
                str_contains(strtolower($user->first_name ?? ''), $searchLower) ||
                str_contains(strtolower($user->last_name ?? ''), $searchLower) ||
                str_contains(strtolower($user->email ?? ''), $searchLower) ||
                str_contains(strtolower($user->phone_number ?? ''), $searchLower) ||
                str_contains(strtolower($user->iqama ?? ''), $searchLower)
            ) {
                return true;
            }

            // Check merchant business name
            if ($user->merchant && str_contains(strtolower($user->merchant->business_name ?? ''), $searchLower)) {
                return true;
            }

            // Check customer business name
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

        // Extract business names for Google prefetching from filtered results
        $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers($filtered);

        if (!empty($businessNames)) {
            $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
        }

        // Calculate risks for all filtered results
        // calculateForUsers loads per-user weights for each user and returns results in the same order as $filtered
        $risksAll = $this->riskAnalyticsService->calculateForUsers($filtered, $weights);

        // DO NOT re-sort by score — keep latest-user-first order
        $orderedAll = $risksAll->values();

        // Manual pagination (order preserved)
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



    /**
     * Export CSV - uses service to compute scores for all filtered users
     */
    public function exportCsv(Request $request)
    {
        $users = $this->getUsersWithFilters($request); // Collection of User
        $risks = $this->riskAnalyticsService->calculateForUsers($users);

        $filename = 'risk_scores_' . date('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($risks) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Name',
                'Business Name',
                'CR Number',
                'ID Number',
                'CR/ID Match Score',
                'ID Expiry Score',
                'CR Expiry Score',
                'Business Type Score',
                'Activity Score',
                'CR/ID Total',
                'CR/ID Score',
                'POS Revenue',
                'POS Score',
                'Late Payments',
                'Repayment Score',
                'Industry',
                'Industry Score',
                'City',
                'City Tier Score',
                'Economic Activity Score',
                'Default Rate Score',
                'Location Score',
                'Total Score'
            ]);

            foreach ($risks as $risk) {
                fputcsv($handle, [
                    $risk->id,
                    $risk->name,
                    $risk->business_name,
                    $risk->cr_number,
                    $risk->id_number,
                    $risk->cr_id_match_score,
                    $risk->id_expiry_score,
                    $risk->cr_expiry_score,
                    $risk->business_type_score,
                    $risk->activity_score,
                    $risk->cr_id_total,
                    $risk->cr_id_score,
                    $risk->pos_revenue,
                    $risk->pos_score,
                    $risk->late_payments,
                    $risk->repayment_score,
                    $risk->industry,
                    $risk->industry_score,
                    $risk->location['city'] ?? '-',
                    $risk->location['tier_score'] ?? 0,
                    $risk->location['activity_score'] ?? 0,
                    $risk->location['default_rate_score'] ?? 0,
                    $risk->location_score,
                    $risk->total_score,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }

    /**
     * Export PDF - uses service to compute scores and passes to PDF view
     */
    public function exportPdf(Request $request)
    {
        $users = $this->getUsersWithFilters($request);
        $risks = $this->riskAnalyticsService->calculateForUsers($users);

        $pdf = Pdf::loadView('admin.risk-management.pdf', ['risks' => $risks])
            ->setPaper('A4', 'landscape');

        return $pdf->stream('risk_scores_' . date('Ymd_His') . '.pdf');
    }

    /**
     * Helper: returns a collection of users obeying the same filters as in score()
     */
    private function getUsersWithFilters(Request $request)
    {
        $search = $request->input('search');

        $usersQuery = User::whereIn('user_type', ['merchant', 'user'])
            ->with(['merchant.businessType', 'customer.businessType', 'transactions']);

        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('iqama', 'like', "%{$search}%")
                    ->orWhereHas('merchant', function ($q2) use ($search) {
                        $q2->where('business_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('customer', function ($q3) use ($search) {
                        $q3->where('business_name', 'like', "%{$search}%");
                    });
            });
        }

        return $usersQuery->get();
    }

    /**
     * Update manual risk score (unchanged)
     */
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
