<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\RiskScore;
use App\Models\RiskWeight;
use App\Models\User;
use App\Services\RiskAnalyticsService;
use App\Services\RiskDashboardService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RiskAnalyticsController extends Controller
{
    protected $riskDashboardService;
    protected $riskAnalyticsService;
    protected $riskService;

    public function __construct(
        RiskDashboardService $riskDashboardService,
        RiskAnalyticsService $riskAnalyticsService,
        RiskService $riskService
    ) {
        $this->riskDashboardService = $riskDashboardService;
        $this->riskAnalyticsService = $riskAnalyticsService;
        $this->riskService = $riskService;
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

    public function allAlerts(Request $request)
    {
        $filters = [
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to')
        ];

        $allAlerts = $this->riskDashboardService->getAllAlerts($filters);

        return view('admin.risk-management.alerts-index', [
            'alerts' => $allAlerts,
            'filters' => $filters,
            'totalAlerts' => count($allAlerts),
            'criticalCount' => collect($allAlerts)->where('severity_level', 'critical')->count(),
            'highCount' => collect($allAlerts)->where('severity_level', 'high')->count(),
            'mediumCount' => collect($allAlerts)->where('severity_level', 'medium')->count(),
            'informationCount' => collect($allAlerts)->where('severity_level', 'info')->count(),
        ]);
    }

    // public function score(Request $request)
    // {
    //     $search = trim($request->input('search', ''));
    //     $order = $request->input('order', 'desc');
    //     $perPage = 10;

    //     $typeParam = strtolower($request->input('type', ''));
    //     $allowedTypes = ['merchant', 'user'];
    //     $userTypes = in_array($typeParam, $allowedTypes) ? [$typeParam] : $allowedTypes;

    //     $weights = array_filter([
    //         'cr_id' => $request->input('weight_cr_id'),
    //         'pos' => $request->input('weight_pos'),
    //         'repayment' => $request->input('weight_repayment'),
    //         'industry' => $request->input('weight_industry'),
    //         'location' => $request->input('weight_location'),
    //     ], fn($weight) => !is_null($weight));

    //     $baseQuery = User::query()
    //         ->whereIn('user_type', $userTypes)
    //         ->where(function ($query) {
    //             $query->whereHas('merchant')
    //                 ->orWhereHas('customer');
    //         })
    //         ->with(['merchant.businessType', 'customer.businessType', 'transactions'])
    //         ->orderBy('created_at', $order);

    //     if ($search) {
    //         $searchLower = strtolower($search);

    //         $baseQuery->where(function ($query) use ($searchLower) {
    //             $query->where('first_name', 'like', "%{$searchLower}%")
    //                 ->orWhere('last_name', 'like', "%{$searchLower}%")
    //                 ->orWhere('email', 'like', "%{$searchLower}%")
    //                 ->orWhere('phone_number', 'like', "%{$searchLower}%")
    //                 ->orWhere('iqama', 'like', "%{$searchLower}%");

    //             $query->orWhereHas('merchant', function ($q) use ($searchLower) {
    //                 $q->where('business_name', 'like', "%{$searchLower}%");
    //             });

    //             $query->orWhereHas('customer', function ($q) use ($searchLower) {
    //                 $q->where('business_name', 'like', "%{$searchLower}%");
    //             });
    //         });
    //     }

    //     $usersPaginator = $baseQuery->paginate($perPage);
    //     $usersCollection = $usersPaginator->getCollection();

    //     if ($usersCollection->isEmpty()) {
    //         return view('admin.risk-management.score', [
    //             'risks' => new LengthAwarePaginator(collect(), 0, $perPage, 1, [
    //                 'path' => request()->url(),
    //                 'query' => request()->query(),
    //             ])
    //         ]);
    //     }

    //     $businessNames = $this->riskAnalyticsService->extractBusinessNamesFromUsers($usersCollection);
    //     if (!empty($businessNames)) {
    //         $this->riskAnalyticsService->prefetchGoogleRatings($businessNames);
    //     }

    //     $risksCollection = $this->riskAnalyticsService->calculateForUsers(
    //         $usersCollection,
    //         $weights
    //     );

    //     $paginatedRisks = new LengthAwarePaginator(
    //         $risksCollection->values(),
    //         $usersPaginator->total(),
    //         $usersPaginator->perPage(),
    //         $usersPaginator->currentPage(),
    //         ['path' => request()->url(), 'query' => request()->query()]
    //     );

    //     return view('admin.risk-management.score', ['risks' => $paginatedRisks]);
    // }

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

    public function merchantScore(Request $request)
    {
        $search = trim($request->input('search', ''));
        $order = $request->input('order', 'desc');
        $perPage = (int) $request->input('per_page', 10);

        $typeParam = strtolower($request->input('type', ''));
        $allowedTypes = ['merchant', 'user'];
        $userTypes = in_array($typeParam, $allowedTypes) ? [$typeParam] : $allowedTypes;

        // Build base query - only users that have either merchant OR customer records
        $baseQuery = User::query()
            ->whereIn('user_type', $userTypes)
            ->where(function ($q) use ($userTypes) {
                if (in_array('merchant', $userTypes)) {
                    $q->orWhereHas('merchant');
                }
                if (in_array('user', $userTypes)) {
                    $q->orWhereHas('customer');
                }
            })
            ->with(['merchant.businessType', 'customer.businessType', 'transactions'])
            ->orderBy('created_at', $order);

        // paginate users first
        $usersPaginator = $baseQuery->paginate($perPage)->appends($request->query());
        $usersCollection = $usersPaginator->getCollection();

        // Apply PHP filtering for encrypted fields
        if ($search) {
            $usersCollection = $usersCollection->filter(function ($user) use ($search) {
                $searchLower = strtolower(trim($search));
                $searchTerms = explode(' ', $searchLower);

                // Check full fields
                if (
                    str_contains(strtolower($user->first_name), $searchLower) ||
                    str_contains(strtolower($user->last_name), $searchLower) ||
                    str_contains(strtolower($user->email), $searchLower) ||
                    str_contains(strtolower($user->phone_number), $searchLower) ||
                    str_contains(strtolower($user->business_name), $searchLower)
                ) {
                    return true;
                }

                // Check first_name / last_name term by term
                foreach ($searchTerms as $term) {
                    if (
                        str_contains(strtolower($user->first_name), $term) ||
                        str_contains(strtolower($user->last_name), $term)
                    ) {
                        return true;
                    }
                }

                return false;
            })->values(); // reindex collection
        }

        // If nothing, return empty paginator
        if ($usersCollection->isEmpty()) {
            $empty = new LengthAwarePaginator(collect(), 0, $perPage, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return view('admin.risk-management.merchant-score', ['risks' => $empty]);
        }

        // map users -> compute risk
        $risksCollection = $usersCollection->map(function (User $user) {
            if ($user->user_type === 'merchant') {
                $entity = $user->merchant;
                $entityType = 'merchant';

                if (!$entity) {
                    return [
                        'user' => $user,
                        'risk' => [
                            'error' => 'merchant_record_not_found',
                            'message' => 'User is marked as merchant but no merchant record found',
                        ],
                        'type' => $entityType,
                        'skipped' => true,
                    ];
                }

                $profileRoute = 'supplierProfile';
                $profileId = $entity->id;
            } else {
                $entity = $user->customer;
                $entityType = 'customer';

                if (!$entity) {
                    return [
                        'user' => $user,
                        'risk' => [
                            'error' => 'customer_record_not_found',
                            'message' => 'User is marked as customer but no customer record found',
                        ],
                        'type' => $entityType,
                        'skipped' => true,
                    ];
                }

                $profileRoute = 'customerProfile';
                $profileId = $entity->id;
            }

            try {
                $risk = $this->riskService->analyzeCustomer($entity, $entityType);
            } catch (\Throwable $e) {
                $risk = [
                    'error' => 'risk_service_error',
                    'message' => $e->getMessage(),
                ];
            }

            return [
                'user' => $user,
                'risk' => $risk,
                'type' => $entityType,
                'profile_route' => $profileRoute,
                'profile_id' => $profileId,
                'skipped' => false,
            ];
        });

        // Filter out skipped users
        $processedRisksCollection = $risksCollection->filter(function ($item) {
            return !isset($item['skipped']) || $item['skipped'] === false;
        });

        // Create paginated result
        $paginatedRisks = new LengthAwarePaginator(
            $processedRisksCollection->values(),
            $usersPaginator->total(),
            $usersPaginator->perPage(),
            $usersPaginator->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.risk-management.merchant-score', ['risks' => $paginatedRisks]);
    }

    /**
     * Show detailed risk analysis for a user
     */
    public function show($userId, $type = 'customer')
    {
        if (!hasSensitivePermission('risk_drivers_aggregated')) {
            return back()->with('error', translate('Access Restricted'));
        }

        try {
            $user = User::findOrFail($userId);

            // Get the entity based on type
            if ($type === 'merchant') {
                $entity = Merchant::where('user_id', $userId)->first();
                if (!$entity) {
                    $entity = Merchant::where('user_id', $userId)->first();
                }
            } else {
                $entity = Customer::where('user_id', $userId)->first();
            }

            if (!$entity) {
                return back()->with('error', ucfirst($type) . ' record not found');
            }

            // Get complete risk analysis
            $riskAnalysis = $this->riskService->analyzeCustomer($entity, $type);

            $riskData = $this->riskDashboardService->getUserDashboardData($userId, [
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-31'
            ]);

            return view('admin.risk-management.details', compact('user', 'riskAnalysis', 'type', 'riskData'));
        } catch (\Exception $e) {
            return back()->with('error', 'Error loading risk analysis: ' . $e->getMessage());
        }
    }

    /**
     * Get risk components data for AJAX updates
     */
    public function components($userId, $type)
    {
        try {
            $user = User::findOrFail($userId);

            if ($type === 'merchant') {
                $entity = Merchant::where('seller_id', $userId)->first();
                if (!$entity) {
                    $entity = Merchant::where('user_id', $userId)->first();
                }
            } else {
                $entity = Customer::where('user_id', $userId)->first();
            }

            if (!$entity) {
                return response()->json(['error' => 'Entity not found'], 404);
            }

            $riskAnalysis = $this->riskService->analyzeCustomer($entity, $type);

            return response()->json($riskAnalysis);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
