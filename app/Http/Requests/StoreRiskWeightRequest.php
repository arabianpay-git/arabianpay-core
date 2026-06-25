<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskWeightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('risk_drivers_aggregated');
    }

    public function rules(): array
    {
        return [
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
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $fields = ['lps_weight', 'chs_weight', 'bcs_weight', 'bps_weight', 'bes_weight'];
            $sum = collect($fields)->sum(fn ($field) => (float) $this->input($field, 0));
            if (abs($sum - 100) > 0.001) {
                $validator->errors()->add('weights', 'The risk weights must sum to 100.');
            }
        });
    }
}
