<?php

namespace App\Http\Controllers;

use App\Models\RiskWeight;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RiskWeightController extends Controller
{
    /**
     * Store or update risk weights for a user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',

            // Main weights
            'lps_weight' => 'required|numeric|min:0|max:100',
            'chs_weight' => 'required|numeric|min:0|max:100',
            'bcs_weight' => 'required|numeric|min:0|max:100',
            'bps_weight' => 'required|numeric|min:0|max:100',
            'bes_weight' => 'required|numeric|min:0|max:100',
            'caf_weight' => 'required|numeric|min:0|max:100',

            // LPS sub-weights
            'lps_age_weight' => 'required|numeric|min:0|max:100',
            'lps_cr_weight' => 'required|numeric|min:0|max:100',
            'lps_doc_weight' => 'required|numeric|min:0|max:100',

            // BCS sub-weights
            'bcs_turnover_weight' => 'required|numeric|min:0|max:100',
            'bcs_volatility_weight' => 'required|numeric|min:0|max:100',
            'bcs_returned_weight' => 'required|numeric|min:0|max:100',
            'bcs_balance_weight' => 'required|numeric|min:0|max:100',

            // BPS sub-weights
            'bps_sector_weight' => 'required|numeric|min:0|max:100',
            'bps_region_weight' => 'required|numeric|min:0|max:100',

            // BES sub-weights
            'bes_dpd_weight' => 'required|numeric|min:0|max:100',
            'bes_utilization_weight' => 'required|numeric|min:0|max:100',
            'bes_dispute_weight' => 'required|numeric|min:0|max:100',
            'bes_trend_weight' => 'required|numeric|min:0|max:100',
        ]);

        // Validate main weights sum to 100
        $mainWeights = collect($validated)->only([
            'lps_weight',
            'chs_weight',
            'bcs_weight',
            'bps_weight',
            'bes_weight',
            'caf_weight'
        ]);
        if (abs($mainWeights->sum() - 100) > 0.01) {
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
                $existingWeight->update($data);
                $riskWeight = $existingWeight;
            } else {
                $riskWeight = RiskWeight::create($data);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Risk weights saved successfully.',
                'data' => $riskWeight,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to save risk weights: ' . $e->getMessage(),
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

        if (!empty($settingWeights) && is_array($settingWeights)) {
            // Merge settings weights with hardcoded defaults (settings take precedence)
            $finalWeights = array_merge($hardcodedDefaults, $settingWeights);

            return response()->json([
                'success' => true,
                'weights' => $finalWeights,
                'source' => 'settings',
                'is_default' => true,
                'message' => 'Using system default weights from settings',
            ]);
        }

        // If no settings found, use hardcoded defaults
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
            $existing->delete();
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

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }
}
