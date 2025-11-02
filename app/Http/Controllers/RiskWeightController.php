<?php

namespace App\Http\Controllers;

use App\Models\RiskWeight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RiskWeightController extends Controller
{
    /**
     * Store new weights — if a weights row already exists, update that row instead
     * so the app keeps a single row (latest) and stores last_weight/new_weight history.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cr_id' => 'required|numeric|min:0',
            'pos' => 'required|numeric|min:0',
            'repayment' => 'required|numeric|min:0',
            'industry' => 'required|numeric|min:0',
            'location' => 'required|numeric|min:0',
        ]);

        $weights = $request->only(['cr_id', 'pos', 'repayment', 'industry', 'location']);

        // Validate sum equals 100
        $sum = array_sum($weights);
        if ($sum != 95) {
            return response()->json([
                'success' => false,
                'message' => 'The sum of all weights must equal 100. Current sum: ' . $sum
            ], 422);
        }

        $last = RiskWeight::latest()->first();

        if ($last) {
            // Update existing row and keep previous new_weight in last_weight
            $last->update([
                'employee_id' => Auth::id(),
                'last_weight' => $last->new_weight,
                'new_weight' => $weights,
                'cr_id' => $weights['cr_id'],
                'pos' => $weights['pos'],
                'repayment' => $weights['repayment'],
                'industry' => $weights['industry'],
                'location' => $weights['location'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Weights updated (existing record updated).',
                'data' => $last
            ]);
        }

        // No existing row — create first entry
        $riskWeight = RiskWeight::create([
            'employee_id' => Auth::id(),
            'last_weight' => null,
            'new_weight' => $weights,
            'cr_id' => $weights['cr_id'],
            'pos' => $weights['pos'],
            'repayment' => $weights['repayment'],
            'industry' => $weights['industry'],
            'location' => $weights['location'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Weights added successfully.',
            'data' => $riskWeight
        ], 201);
    }

    /**
     * Update existing weights explicitly by id (keeps last/new history)
     */
    public function update(Request $request, RiskWeight $riskWeight)
    {
        $request->validate([
            'cr_id' => 'required|numeric|min:0',
            'pos' => 'required|numeric|min:0',
            'repayment' => 'required|numeric|min:0',
            'industry' => 'required|numeric|min:0',
            'location' => 'required|numeric|min:0',
        ]);

        $newWeights = $request->only(['cr_id', 'pos', 'repayment', 'industry', 'location']);

        // Validate sum equals 100
        $sum = array_sum($newWeights);
        if ($sum != 95) {
            return response()->json([
                'success' => false,
                'message' => 'The sum of all weights must equal 100. Current sum: ' . $sum
            ], 422);
        }

        $riskWeight->update([
            'employee_id' => Auth::id(),
            'last_weight' => $riskWeight->new_weight,
            'new_weight' => $newWeights,
            'cr_id' => $newWeights['cr_id'],
            'pos' => $newWeights['pos'],
            'repayment' => $newWeights['repayment'],
            'industry' => $newWeights['industry'],
            'location' => $newWeights['location'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Weights updated successfully.',
            'data' => $riskWeight
        ]);
    }

    /**
     * Return the single/latest weights row if exists
     */
    public function getLast()
    {
        $last = RiskWeight::latest()->first();

        if (!$last) {
            return response()->json(['success' => false, 'weights' => null]);
        }

        return response()->json([
            'success' => true,
            'weights' => [
                'id' => $last->id,
                'cr_id' => $last->cr_id,
                'pos' => $last->pos,
                'repayment' => $last->repayment,
                'industry' => $last->industry,
                'location' => $last->location,
                'new_weight' => $last->new_weight,
                'last_weight' => $last->last_weight,
            ],
        ]);
    }
}
