<?php

namespace App\Http\Controllers\settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RiskWeightController extends Controller
{
    protected $defaults = [
        // main
        'lps_weight' => 15,
        'chs_weight' => 25,
        'bcs_weight' => 20,
        'bps_weight' => 10,
        'bes_weight' => 30,
        'caf_weight' => 0,

        // lps sub
        'lps_age_weight' => 40,
        'lps_cr_weight' => 30,
        'lps_doc_weight' => 30,

        // bcs sub
        'bcs_turnover_weight' => 35,
        'bcs_volatility_weight' => 25,
        'bcs_returned_weight' => 25,
        'bcs_balance_weight' => 15,

        // bps sub
        'bps_sector_weight' => 70,
        'bps_region_weight' => 30,

        // bes sub
        'bes_dpd_weight' => 40,
        'bes_utilization_weight' => 25,
        'bes_dispute_weight' => 25,
        'bes_trend_weight' => 10,
    ];

    protected $groups = [
        'main' => ['lps_weight', 'chs_weight', 'bcs_weight', 'bps_weight', 'bes_weight', 'caf_weight'],
        'lps' => ['lps_age_weight', 'lps_cr_weight', 'lps_doc_weight'],
        'bcs' => ['bcs_turnover_weight', 'bcs_volatility_weight', 'bcs_returned_weight', 'bcs_balance_weight'],
        'bps' => ['bps_sector_weight', 'bps_region_weight'],
        'bes' => ['bes_dpd_weight', 'bes_utilization_weight', 'bes_dispute_weight', 'bes_trend_weight'],
    ];

    public function index()
    {
        $saved = Setting::getByKey('risk_weights', null);
        $values = is_array($saved) ? $saved : $this->defaults;

        return view('settings.risk-weight.index', [
            'values' => $values,
            'defaults' => $this->defaults,
        ]);
    }

    public function store(Request $request)
    {
        $expected = array_merge(...array_values($this->groups));

        $rules = [];
        foreach ($expected as $k) {
            $rules[$k] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        $v = Validator::make($request->all(), $rules);

        if ($v->fails()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
            }

            return redirect()->back()->withErrors($v)->withInput();
        }

        $payload = [];
        foreach ($expected as $k) {
            $payload[$k] = round((float) $request->input($k, 0), 2);
        }

        $errors = [];
        foreach ($this->groups as $groupName => $keys) {
            $sum = 0;
            foreach ($keys as $k) {
                $sum += ($payload[$k] ?? 0);
            }
            if (abs($sum - 100) > 0.01) {
                $errors[$groupName] = "The {$groupName} weights must total 100. Current total: ".number_format($sum, 2);
            }
        }

        if (! empty($errors)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'Sum validation failed', 'errors' => $errors], 422);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        }

        // This will update existing or create new (no duplicates)
        Setting::setByKey('risk_weights', $payload, 'risk', 'json', false);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Risk weights saved successfully', 'data' => $payload], 200);
        }

        // make sure route name matches your routes/web.php
        return redirect()->route('settings.risk-weight.index')->with('success', 'Risk weights saved successfully');
    }
}
