<?php

namespace App\Http\Controllers;

use App\Models\RiskWeight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskWeightController extends Controller
{
    /**
     * Store new weights — if a record already exists for this user_id, update it instead.
     * Keeps last_weight / new_weight history.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Required user reference
            'user_id' => 'required|integer|exists:users,id',

            // Main weights
            'cr_id' => 'required|numeric|min:0',
            'pos' => 'required|numeric|min:0',
            'repayment' => 'required|numeric|min:0',
            'industry' => 'required|numeric|min:0',
            'location' => 'required|numeric|min:0',

            // CR / ID sub-weights
            'cr_id_sub_id_match' => 'nullable|numeric|min:0',
            'cr_id_sub_id_expiry' => 'nullable|numeric|min:0',
            'cr_id_sub_cr_expiry' => 'nullable|numeric|min:0',
            'cr_id_sub_industry' => 'nullable|numeric|min:0',
            'cr_id_sub_activity' => 'nullable|numeric|min:0',
            'cr_id_sub_total' => 'nullable|numeric|min:0',

            // POS
            'pos_threshold' => 'nullable|numeric|min:0',

            // Repayment
            'repayment_few_threshold' => 'nullable|integer|min:0',
            'repayment_score_no_delays' => 'nullable|numeric|min:0',
            'repayment_score_few_delays' => 'nullable|numeric|min:0',
            'repayment_score_many_delays' => 'nullable|numeric|min:0',

            // Location
            'location_activity_max' => 'nullable|numeric|min:0',
            'location_default_rate_max' => 'nullable|numeric|min:0',
            'location_sub_total_max' => 'nullable|numeric|min:0',
        ]);

        // ---- Validate total of main weights ----
        $mainWeights = collect($validated)->only(['cr_id', 'pos', 'repayment', 'industry', 'location']);
        $sum = $mainWeights->sum();

        if ($sum != 95) {
            return response()->json([
                'success' => false,
                'message' => "The sum of all main weights must equal 95. Current sum: {$sum}",
            ], 422);
        }

        $userId = $validated['user_id'];

        // ---- Find latest record for this user ----
        $last = RiskWeight::where('user_id', $userId)->latest()->first();

        $data = array_merge(
            $validated,
            [
                'employee_id' => Auth::id(), // logged-in user making the change
                'last_weight' => $last ? $last->new_weight : null,
                'new_weight' => $mainWeights,
            ]
        );

        if ($last) {
            $last->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Weights updated (existing record updated).',
                'data' => $last,
            ]);
        }

        $riskWeight = RiskWeight::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Weights added successfully.',
            'data' => $riskWeight,
        ], 201);
    }

    /**
     * Update existing weights explicitly by ID (keeps last/new history)
     */
    public function update(Request $request, RiskWeight $riskWeight)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',

            // Main weights
            'cr_id' => 'required|numeric|min:0',
            'pos' => 'required|numeric|min:0',
            'repayment' => 'required|numeric|min:0',
            'industry' => 'required|numeric|min:0',
            'location' => 'required|numeric|min:0',

            // CR / ID sub-weights
            'cr_id_sub_id_match' => 'nullable|numeric|min:0',
            'cr_id_sub_id_expiry' => 'nullable|numeric|min:0',
            'cr_id_sub_cr_expiry' => 'nullable|numeric|min:0',
            'cr_id_sub_industry' => 'nullable|numeric|min:0',
            'cr_id_sub_activity' => 'nullable|numeric|min:0',
            'cr_id_sub_total' => 'nullable|numeric|min:0',

            // POS
            'pos_threshold' => 'nullable|numeric|min:0',

            // Repayment
            'repayment_few_threshold' => 'nullable|integer|min:0',
            'repayment_score_no_delays' => 'nullable|numeric|min:0',
            'repayment_score_few_delays' => 'nullable|numeric|min:0',
            'repayment_score_many_delays' => 'nullable|numeric|min:0',

            // Location
            'location_activity_max' => 'nullable|numeric|min:0',
            'location_default_rate_max' => 'nullable|numeric|min:0',
            'location_sub_total_max' => 'nullable|numeric|min:0',
        ]);

        $mainWeights = collect($validated)->only(['cr_id', 'pos', 'repayment', 'industry', 'location']);
        $sum = $mainWeights->sum();

        if ($sum != 95) {
            return response()->json([
                'success' => false,
                'message' => "The sum of all main weights must equal 95. Current sum: {$sum}",
            ], 422);
        }

        $data = array_merge(
            $validated,
            [
                'employee_id' => Auth::id(),
                'last_weight' => $riskWeight->new_weight,
                'new_weight' => $mainWeights,
            ]
        );

        $riskWeight->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Weights updated successfully.',
            'data' => $riskWeight,
        ]);
    }

    /**
     * Return the single/latest weights row if exists for provided user_id
     */
    public function getLast(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $last = RiskWeight::where('user_id', $request->user_id)->latest()->first();

        if (!$last) {
            return response()->json(['success' => false, 'weights' => null]);
        }

        return response()->json([
            'success' => true,
            'weights' => $last,
        ]);
    }
}
