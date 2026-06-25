<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Merchant;
use App\Models\RiskScore;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\RiskAnalyticsService;
use App\Services\RiskDashboardService;
use App\Services\RiskService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RiskAnalyticsController extends Controller
{
    protected $riskDashboardService;

    protected $riskAnalyticsService;

    protected $riskService;

    protected $auditTrailService;

    public function __construct(
        RiskDashboardService $riskDashboardService,
        RiskAnalyticsService $riskAnalyticsService,
        RiskService $riskService,
        AuditTrailService $auditTrailService
    ) {
        $this->riskDashboardService = $riskDashboardService;
        $this->riskAnalyticsService = $riskAnalyticsService;
        $this->riskService = $riskService;
        $this->auditTrailService = $auditTrailService;
    }

    public function dashboard(Request $request)
    {
        try {
            $filters = [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $dashboardData = $this->riskDashboardService->getDashboardData($filters);

            // Log dashboard access with justification for viewing risk data
            $justificationData = $this->auditTrailService->withJustification(
                'Risk dashboard access required for monitoring portfolio health, early warning signals, and risk exposure',
                'legitimate_interest',
                ['portfolio_metrics', 'risk_scores', 'alert_counts', 'pipeline_data']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'risk_operations',
                'event_type' => 'risk_dashboard_view',
                'entity_type' => 'RiskDashboard',
                'action_summary' => 'Viewed risk management dashboard',
                'properties' => [
                    'filters_applied' => $filters,
                    'portfolio_data_available' => ! empty($dashboardData['portfolio']),
                    'pipeline_data_available' => ! empty($dashboardData['pipeline']),
                    'ews_data_available' => ! empty($dashboardData['ews']),
                    'risk_scores_count' => count($dashboardData['risk_scores'] ?? []),
                    'active_alerts_count' => count($dashboardData['alerts'] ?? []),
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type,
                ],
            ], $justificationData));

            return view('admin.risk-management.dashboard', [
                'portfolioData' => $dashboardData['portfolio'],
                'pipelineData' => $dashboardData['pipeline'],
                'ewsData' => $dashboardData['ews'],
                'riskScores' => $dashboardData['risk_scores'],
                'activeAlerts' => $dashboardData['alerts'],
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load risk dashboard', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_dashboard_failed',
                'entity_type' => 'RiskDashboard',
                'action_summary' => 'Failed to load risk management dashboard',
                'properties' => [
                    'error' => $e->getMessage(),
                    'filters' => $request->all(),
                    'attempted_by' => Auth::id(),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to load risk dashboard. Please try again.');
        }
    }

    public function allAlerts(Request $request)
    {
        try {
            $filters = [
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ];

            $allAlerts = $this->riskDashboardService->getAllAlerts($filters);

            $criticalCount = collect($allAlerts)->where('severity_level', 'critical')->count();
            $highCount = collect($allAlerts)->where('severity_level', 'high')->count();
            $mediumCount = collect($allAlerts)->where('severity_level', 'medium')->count();
            $informationCount = collect($allAlerts)->where('severity_level', 'info')->count();

            // Log alerts view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Risk alerts review required for monitoring potential threats, compliance issues, and operational risks',
                'legitimate_interest',
                ['alert_severity', 'alert_types', 'entity_references']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'risk_operations',
                'event_type' => 'risk_alerts_view',
                'entity_type' => 'RiskAlert',
                'action_summary' => 'Viewed all risk alerts',
                'properties' => [
                    'filters_applied' => $filters,
                    'total_alerts' => count($allAlerts),
                    'critical_alerts' => $criticalCount,
                    'high_alerts' => $highCount,
                    'medium_alerts' => $mediumCount,
                    'information_alerts' => $informationCount,
                    'date_range' => $filters['date_from'] && $filters['date_to']
                        ? $filters['date_from'].' to '.$filters['date_to']
                        : 'All time',
                    'viewed_by' => Auth::id(),
                ],
            ], $justificationData));

            return view('admin.risk-management.alerts-index', [
                'alerts' => $allAlerts,
                'filters' => $filters,
                'totalAlerts' => count($allAlerts),
                'criticalCount' => $criticalCount,
                'highCount' => $highCount,
                'mediumCount' => $mediumCount,
                'informationCount' => $informationCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load risk alerts', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_alerts_view_failed',
                'entity_type' => 'RiskAlert',
                'action_summary' => 'Failed to load risk alerts',
                'properties' => [
                    'error' => $e->getMessage(),
                    'filters' => $request->all(),
                    'attempted_by' => Auth::id(),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to load risk alerts. Please try again.');
        }
    }

    public function scoreUpdate(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'risk_score' => 'required|numeric|min:0|max:100',
                'reason' => 'required|string|max:500',
            ]);

            $user = User::findOrFail($request->user_id);

            // Get existing risk score if any
            $existingRiskScore = RiskScore::where('user_id', $request->user_id)->first();
            $beforeState = $existingRiskScore ? $existingRiskScore->toArray() : null;

            $riskScore = RiskScore::updateOrCreate(
                ['user_id' => $request->user_id],
                [
                    'risk_score' => $request->risk_score,
                    'reason' => $request->reason,
                    'updated_by' => Auth::id(),
                ]
            );

            // Log risk score update with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Risk score manual adjustment required for accurate risk assessment and credit decisioning',
                'legitimate_interest',
                ['risk_score', 'user_id', 'adjustment_reason']
            );

            $actionSummary = $beforeState
                ? 'Updated risk score for user #'.$user->id
                : 'Created risk score for user #'.$user->id;

            $properties = [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_type' => $user->user_type,
                'old_risk_score' => $beforeState['risk_score'] ?? 'Not set',
                'new_risk_score' => $request->risk_score,
                'adjustment_reason' => $request->reason,
                'adjusted_by' => Auth::id(),
                'adjusted_by_type' => Auth::user()->user_type,
            ];

            if ($beforeState) {
                $this->auditTrailService->logUpdated(
                    $riskScore,
                    $beforeState,
                    $actionSummary,
                    array_merge([
                        'event_category' => 'risk_operations',
                        'event_type' => 'risk_score_updated',
                        'entity_type' => 'RiskScore',
                    ], $justificationData, [
                        'properties' => $properties,
                    ])
                );
            } else {
                $this->auditTrailService->logCreated(
                    $riskScore,
                    $actionSummary,
                    array_merge([
                        'event_category' => 'risk_operations',
                        'event_type' => 'risk_score_created',
                        'entity_type' => 'RiskScore',
                    ], $justificationData, [
                        'properties' => $properties,
                    ])
                );
            }

            session()->forget('otp_verified');

            return redirect()->back()->with('success', 'Risk score updated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to update risk score', [
                'error' => $e->getMessage(),
                'user_id' => $request->user_id ?? 'unknown',
                'risk_score' => $request->risk_score ?? 'unknown',
                'updated_by' => Auth::id(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_score_update_failed',
                'entity_type' => 'RiskScore',
                'entity_id' => $request->user_id ?? null,
                'action_summary' => 'Failed to update risk score',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => $request->user_id ?? 'unknown',
                    'requested_score' => $request->risk_score ?? 'unknown',
                    'reason' => substr($request->reason ?? '', 0, 200),
                    'attempted_by' => Auth::id(),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to update risk score. Please try again.');
        }
    }

    public function merchantScore(Request $request)
    {
        try {
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

                // Log empty search results
                $this->auditTrailService->logViewOperation(
                    'risk_scores_search',
                    'RiskScore',
                    'Searched risk scores with no results',
                    [
                        'search_query' => $search,
                        'user_types' => $userTypes,
                        'order' => $order,
                        'per_page' => $perPage,
                        'results_count' => 0,
                    ]
                );

                return view('admin.risk-management.merchant-score', ['risks' => $empty]);
            }

            // map users -> compute risk
            $risksCollection = $usersCollection->map(function (User $user) {
                if ($user->user_type === 'merchant') {
                    $entity = $user->merchant;
                    $entityType = 'merchant';

                    if (! $entity) {
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

                    if (! $entity) {
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
                return ! isset($item['skipped']) || $item['skipped'] === false;
            });

            // Create paginated result
            $paginatedRisks = new LengthAwarePaginator(
                $processedRisksCollection->values(),
                $usersPaginator->total(),
                $usersPaginator->perPage(),
                $usersPaginator->currentPage(),
                ['path' => request()->url(), 'query' => request()->query()]
            );

            // Log risk scores view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Risk scores review required for portfolio risk assessment, credit decisions, and regulatory compliance',
                'legitimate_interest',
                ['risk_scores', 'customer_references', 'business_information']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'risk_operations',
                'event_type' => 'risk_scores_view',
                'entity_type' => 'RiskScore',
                'action_summary' => 'Viewed merchant/customer risk scores',
                'properties' => [
                    'search_query' => $search,
                    'user_types_filtered' => $userTypes,
                    'order_by' => $order,
                    'per_page' => $perPage,
                    'total_results' => $usersPaginator->total(),
                    'current_page' => $usersPaginator->currentPage(),
                    'filtered_count' => $processedRisksCollection->count(),
                    'skipped_count' => $risksCollection->count() - $processedRisksCollection->count(),
                    'viewed_by' => Auth::id(),
                ],
            ], $justificationData));

            return view('admin.risk-management.merchant-score', ['risks' => $paginatedRisks]);
        } catch (\Exception $e) {
            Log::error('Failed to load merchant risk scores', [
                'error' => $e->getMessage(),
                'search' => $request->input('search'),
                'type' => $request->input('type'),
                'user_id' => Auth::id(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_scores_view_failed',
                'entity_type' => 'RiskScore',
                'action_summary' => 'Failed to load merchant/customer risk scores',
                'properties' => [
                    'error' => $e->getMessage(),
                    'search_query' => $request->input('search'),
                    'type_filter' => $request->input('type'),
                    'per_page' => $request->input('per_page', 10),
                    'attempted_by' => Auth::id(),
                ],
            ]);

            return redirect()->back()->with('error', 'Failed to load risk scores. Please try again.');
        }
    }

    /**
     * Show detailed risk analysis for a user
     */
    public function show($userId, $type = 'customer')
    {
        try {
            // Check sensitive permissions first
            if (! hasSensitivePermission('risk_drivers_aggregated')) {
                // Log unauthorized access attempt
                $this->auditTrailService->log([
                    'event_category' => 'access_control',
                    'event_type' => 'unauthorized_risk_details_access',
                    'entity_type' => 'RiskAnalysis',
                    'entity_id' => $userId,
                    'action_summary' => 'User attempted to access risk details without permission',
                    'properties' => [
                        'requested_user_id' => $userId,
                        'entity_type' => $type,
                        'required_permission' => 'risk_drivers_aggregated',
                        'user_has_permission' => false,
                        'attempted_by' => Auth::id(),
                        'attempted_by_type' => Auth::user()->user_type,
                    ],
                ]);

                return back()->with('error', translate('Access Restricted'));
            }

            $user = User::findOrFail($userId);

            // Get the entity based on type
            if ($type === 'merchant') {
                $entity = Merchant::where('user_id', $userId)->first();
                if (! $entity) {
                    $entity = Merchant::where('user_id', $userId)->first();
                }
            } else {
                $entity = Customer::where('user_id', $userId)->first();
            }

            if (! $entity) {
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'risk_analysis_entity_not_found',
                    'entity_type' => ucfirst($type),
                    'entity_id' => $userId,
                    'action_summary' => 'Attempted to view risk analysis for non-existent entity',
                    'properties' => [
                        'user_id' => $userId,
                        'entity_type' => $type,
                        'searched_in' => $type === 'merchant' ? 'Merchant table' : 'Customer table',
                        'attempted_by' => Auth::id(),
                    ],
                ]);

                return back()->with('error', ucfirst($type).' record not found');
            }

            // Get complete risk analysis
            $riskAnalysis = $this->riskService->analyzeCustomer($entity, $type);

            $riskData = $this->riskDashboardService->getUserDashboardData($userId, [
                'date_from' => '2024-01-01',
                'date_to' => '2024-01-31',
            ]);

            // Log risk details view with justification
            $justificationData = $this->auditTrailService->withJustification(
                'Detailed risk analysis required for comprehensive risk assessment, credit decisioning, and regulatory compliance',
                'legitimate_interest',
                ['risk_score', 'risk_factors', 'credit_history', 'financial_data']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'risk_operations',
                'event_type' => 'risk_details_view',
                'entity_type' => ucfirst($type),
                'entity_id' => $userId,
                'action_summary' => 'Viewed detailed risk analysis for '.$type.' #'.$userId,
                'properties' => [
                    'user_id' => $userId,
                    'user_email' => $user->email,
                    'entity_type' => $type,
                    'entity_id' => $entity->id,
                    'risk_score' => $riskAnalysis['final_score'] ?? 'Not calculated',
                    'risk_category' => $riskAnalysis['category'] ?? 'Unknown',
                    'has_comprehensive_analysis' => ! empty($riskAnalysis['components']),
                    'analysis_components_count' => count($riskAnalysis['components'] ?? []),
                    'viewed_by' => Auth::id(),
                    'viewed_by_type' => Auth::user()->user_type,
                    'has_permission' => true,
                ],
            ], $justificationData));

            return view('admin.risk-management.details', compact('user', 'riskAnalysis', 'type', 'riskData'));
        } catch (\Exception $e) {
            Log::error('Error loading risk analysis', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type,
                'attempted_by' => Auth::id(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_analysis_failed',
                'entity_type' => ucfirst($type),
                'entity_id' => $userId,
                'action_summary' => 'Failed to load detailed risk analysis',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'entity_type' => $type,
                    'attempted_by' => Auth::id(),
                ],
            ]);

            return back()->with('error', 'Error loading risk analysis: '.$e->getMessage());
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
                if (! $entity) {
                    $entity = Merchant::where('user_id', $userId)->first();
                }
            } else {
                $entity = Customer::where('user_id', $userId)->first();
            }

            if (! $entity) {
                $this->auditTrailService->log([
                    'event_category' => 'error_events',
                    'event_type' => 'risk_components_entity_not_found',
                    'entity_type' => ucfirst($type),
                    'entity_id' => $userId,
                    'action_summary' => 'Attempted to fetch risk components for non-existent entity',
                    'properties' => [
                        'user_id' => $userId,
                        'entity_type' => $type,
                        'search_criteria' => $type === 'merchant' ? 'seller_id/user_id' : 'user_id',
                        'api_request' => true,
                    ],
                ]);

                return response()->json(['error' => 'Entity not found'], 404);
            }

            $riskAnalysis = $this->riskService->analyzeCustomer($entity, $type);

            // Log API call for risk components
            $justificationData = $this->auditTrailService->withJustification(
                'Risk components API access required for real-time risk monitoring and dashboard updates',
                'legitimate_interest',
                ['risk_components', 'risk_indicators', 'financial_metrics']
            );

            $this->auditTrailService->log(array_merge([
                'event_category' => 'risk_operations',
                'event_type' => 'risk_components_api_call',
                'entity_type' => ucfirst($type),
                'entity_id' => $userId,
                'action_summary' => 'Fetched risk components via API for '.$type.' #'.$userId,
                'properties' => [
                    'user_id' => $userId,
                    'entity_type' => $type,
                    'entity_id' => $entity->id,
                    'api_endpoint' => 'components',
                    'http_method' => 'GET',
                    'components_count' => count($riskAnalysis['components'] ?? []),
                    'final_score' => $riskAnalysis['final_score'] ?? 'Not calculated',
                    'requested_by' => Auth::id(),
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                ],
            ], $justificationData));

            return response()->json($riskAnalysis);
        } catch (\Exception $e) {
            Log::error('Failed to fetch risk components via API', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'type' => $type,
                'request_ip' => request()->ip(),
            ]);

            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'risk_components_api_failed',
                'entity_type' => ucfirst($type),
                'entity_id' => $userId,
                'action_summary' => 'Failed to fetch risk components via API',
                'properties' => [
                    'error' => $e->getMessage(),
                    'user_id' => $userId,
                    'entity_type' => $type,
                    'api_endpoint' => 'components',
                    'http_method' => 'GET',
                    'request_ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
