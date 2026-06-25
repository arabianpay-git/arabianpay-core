<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRiskWeightRequest;
use App\Models\RiskWeight;
use App\Models\Setting;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RiskWeightController extends Controller
{
    protected $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Store or update risk weights for a user
     */
    public function store(StoreRiskWeightRequest $request)
    {
        $validated = $request->validated();

        // Validate main weights sum to 100
        $mainWeights = collect($validated)->only([
            'lps_weight',
            'chs_weight',
            'bcs_weight',
            'bps_weight',
            'bes_weight',
            'caf_weight',
        ]);
        if (abs($mainWeights->sum() - 100) > 0.01) {
            // Log validation failure
            $this->auditTrailService->log([
                'event_category' => 'validation',
                'event_type' => 'weight_sum_invalid',
                'entity_type' => 'RiskWeight',
                'entity_id' => $validated['user_id'],
                'action_summary' => 'Failed to save risk weights - main weights sum invalid',
                'properties' => [
                    'user_id' => $validated['user_id'],
                    'actual_sum' => $mainWeights->sum(),
                    'expected_sum' => 100,
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => "The sum of all main weights must equal 100%. Current sum: {$mainWeights->sum()}%",
            ], 422);
        }

        // Validate sub-weights
        $subWeightsGroups = [
            'LPS' => ['lps_age_weight', 'lps_cr_weight', 'lps_doc_weight'],
            'BCS' => ['bcs_turnover_weight', 'bcs_volatility_weight', 'bcs_returned_weight', 'bcs_balance_weight'],
            'BPS' => ['bps_sector_weight', 'bps_region_weight'],
            'BES' => ['bes_dpd_weight', 'bes_utilization_weight', 'bes_dispute_weight', 'bes_trend_weight'],
        ];

        foreach ($subWeightsGroups as $groupName => $fields) {
            $sum = collect($validated)->only($fields)->sum();
            if (abs($sum - 100) > 0.01) {
                // Log validation failure for sub-weights
                $this->auditTrailService->log([
                    'event_category' => 'validation',
                    'event_type' => 'subweight_sum_invalid',
                    'entity_type' => 'RiskWeight',
                    'entity_id' => $validated['user_id'],
                    'action_summary' => "Failed to save risk weights - {$groupName} sub-weights sum invalid",
                    'properties' => [
                        'user_id' => $validated['user_id'],
                        'group' => $groupName,
                        'actual_sum' => $sum,
                        'expected_sum' => 100,
                    ],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "The sum of {$groupName} sub-weights must equal 100%. Current sum: {$sum}%",
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $userId = $validated['user_id'];
            $existingWeight = RiskWeight::where('user_id', $userId)->first();

            // Prepare snapshot of current weights
            $snapshot = [
                'saved_at' => now()->toDateTimeString(),
                'saved_by' => Auth::id(),
                'weights' => [
                    'lps_weight' => $validated['lps_weight'],
                    'chs_weight' => $validated['chs_weight'],
                    'bcs_weight' => $validated['bcs_weight'],
                    'bps_weight' => $validated['bps_weight'],
                    'bes_weight' => $validated['bes_weight'],
                    'caf_weight' => $validated['caf_weight'],
                ],
                'sub_weights' => [
                    'lps_age_weight' => $validated['lps_age_weight'],
                    'lps_cr_weight' => $validated['lps_cr_weight'],
                    'lps_doc_weight' => $validated['lps_doc_weight'],
                    'bcs_turnover_weight' => $validated['bcs_turnover_weight'],
                    'bcs_volatility_weight' => $validated['bcs_volatility_weight'],
                    'bcs_returned_weight' => $validated['bcs_returned_weight'],
                    'bcs_balance_weight' => $validated['bcs_balance_weight'],
                    'bps_sector_weight' => $validated['bps_sector_weight'],
                    'bps_region_weight' => $validated['bps_region_weight'],
                    'bes_dpd_weight' => $validated['bes_dpd_weight'],
                    'bes_utilization_weight' => $validated['bes_utilization_weight'],
                    'bes_dispute_weight' => $validated['bes_dispute_weight'],
                    'bes_trend_weight' => $validated['bes_trend_weight'],
                ],
            ];

            // Initialize or append history
            $history = $existingWeight && is_array($existingWeight->last_weight)
                ? $existingWeight->last_weight
                : [];
            array_unshift($history, $snapshot);

            // Optional cap on history length
            $maxHistory = 50;
            if (count($history) > $maxHistory) {
                $history = array_slice($history, 0, $maxHistory);
            }

            $data = array_merge($validated, [
                'employee_id' => Auth::id(),
                'last_weight' => $history,
                'new_weight' => $mainWeights->toArray(),
            ]);

            if ($existingWeight) {
                // Get old data for audit trail
                $oldData = $existingWeight->toArray();

                $existingWeight->update($data);
                $riskWeight = $existingWeight;

                // Log weight update
                $this->auditTrailService->log([
                    'event_category' => 'risk_management',
                    'event_type' => 'weights_updated',
                    'entity_type' => 'RiskWeight',
                    'entity_id' => $riskWeight->id,
                    'action_summary' => "Updated risk weights for user ID {$userId}",
                    'properties' => [
                        'user_id' => $userId,
                        'main_weights' => $mainWeights->toArray(),
                        'history_count' => count($history),
                        'updated_by' => Auth::id(),
                    ],
                ]);
            } else {
                $riskWeight = RiskWeight::create($data);

                // Log weight creation
                $this->auditTrailService->log([
                    'event_category' => 'risk_management',
                    'event_type' => 'weights_created',
                    'entity_type' => 'RiskWeight',
                    'entity_id' => $riskWeight->id,
                    'action_summary' => "Created risk weights for user ID {$userId}",
                    'properties' => [
                        'user_id' => $userId,
                        'main_weights' => $mainWeights->toArray(),
                        'created_by' => Auth::id(),
                    ],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Risk weights saved successfully.',
                'data' => $riskWeight,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error
            $this->auditTrailService->log([
                'event_category' => 'error_events',
                'event_type' => 'weights_save_failed',
                'entity_type' => 'RiskWeight',
                'entity_id' => $validated['user_id'] ?? null,
                'action_summary' => 'Failed to save risk weights',
                'properties' => [
                    'user_id' => $validated['user_id'] ?? null,
                    'error_message' => $e->getMessage(),
                    'request_data' => array_keys($validated),
                ],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save risk weights: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get weights for a user
     */
    public function getWeights(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        // First, check if user has custom weights in RiskWeight model
        $userWeights = RiskWeight::where('user_id', $request->user_id)->first();

        if ($userWeights) {
            // User has custom weights, return them
            $weights = $userWeights->toArray();

            // Log weights retrieval
            $this->auditTrailService->log([
                'event_category' => 'risk_management',
                'event_type' => 'weights_retrieved',
                'entity_type' => 'RiskWeight',
                'entity_id' => $userWeights->id,
                'action_summary' => "Retrieved custom risk weights for user ID {$request->user_id}",
                'properties' => [
                    'user_id' => $request->user_id,
                    'source' => 'database',
                    'has_custom_weights' => true,
                ],
            ]);

            return response()->json([
                'success' => true,
                'weights' => $weights,
                'source' => 'database',
                'is_default' => false,
                'message' => 'User-specific weights loaded',
            ]);
        }

        // User doesn't have custom weights, get defaults from Settings
        $settingWeights = Setting::getByKey('risk_weights', []);

        // Get hardcoded defaults from RiskWeight model
        $hardcodedDefaults = RiskWeight::getDefaultWeights();

        if (! empty($settingWeights) && is_array($settingWeights)) {
            // Merge settings weights with hardcoded defaults (settings take precedence)
            $finalWeights = array_merge($hardcodedDefaults, $settingWeights);

            // Log default weights retrieval
            $this->auditTrailService->log([
                'event_category' => 'risk_management',
                'event_type' => 'default_weights_retrieved',
                'entity_type' => 'RiskWeight',
                'entity_id' => $request->user_id,
                'action_summary' => "Retrieved default risk weights for user ID {$request->user_id}",
                'properties' => [
                    'user_id' => $request->user_id,
                    'source' => 'settings',
                    'has_custom_weights' => false,
                ],
            ]);

            return response()->json([
                'success' => true,
                'weights' => $finalWeights,
                'source' => 'settings',
                'is_default' => true,
                'message' => 'Using system default weights from settings',
            ]);
        }

        // If no settings found, use hardcoded defaults
        // Log fallback weights retrieval
        $this->auditTrailService->log([
            'event_category' => 'risk_management',
            'event_type' => 'fallback_weights_retrieved',
            'entity_type' => 'RiskWeight',
            'entity_id' => $request->user_id,
            'action_summary' => "Retrieved fallback risk weights for user ID {$request->user_id}",
            'properties' => [
                'user_id' => $request->user_id,
                'source' => 'fallback',
                'has_custom_weights' => false,
            ],
        ]);

        return response()->json([
            'success' => true,
            'weights' => $hardcodedDefaults,
            'source' => 'fallback',
            'is_default' => true,
            'message' => 'Using fallback default weights',
        ]);
    }

    /**
     * Reset weights to default for a user
     */
    public function resetToDefault(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $existing = RiskWeight::where('user_id', $request->user_id)->first();

        if ($existing) {
            // Log before deletion
            $this->auditTrailService->log([
                'event_category' => 'risk_management',
                'event_type' => 'weights_reset',
                'entity_type' => 'RiskWeight',
                'entity_id' => $existing->id,
                'action_summary' => "Reset risk weights to default for user ID {$request->user_id}",
                'properties' => [
                    'user_id' => $request->user_id,
                    'reset_by' => Auth::id(),
                    'previous_weight_id' => $existing->id,
                ],
            ]);

            $existing->delete();
        } else {
            // Log attempt to reset non-existent weights
            $this->auditTrailService->log([
                'event_category' => 'risk_management',
                'event_type' => 'weights_reset_attempt',
                'entity_type' => 'RiskWeight',
                'entity_id' => $request->user_id,
                'action_summary' => "Attempted to reset non-existent risk weights for user ID {$request->user_id}",
                'properties' => [
                    'user_id' => $request->user_id,
                    'reset_by' => Auth::id(),
                    'had_custom_weights' => false,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Weights reset to default successfully.',
            'weights' => RiskWeight::getDefaultWeights(),
        ]);
    }

    /**
     * Get weight history for a user
     */
    public function getWeightHistory(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $history = RiskWeight::with('employee')
            ->where('user_id', $request->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Log history view
        $this->auditTrailService->log([
            'event_category' => 'risk_management',
            'event_type' => 'history_viewed',
            'entity_type' => 'RiskWeight',
            'entity_id' => $request->user_id,
            'action_summary' => "Viewed risk weight history for user ID {$request->user_id}",
            'properties' => [
                'user_id' => $request->user_id,
                'history_count' => $history->count(),
                'viewed_by' => Auth::id(),
            ],
        ]);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }
}
