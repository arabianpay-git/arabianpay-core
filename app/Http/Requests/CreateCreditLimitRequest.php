<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for creating customer credit limits.
 */
class CreateCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('credit-limit.create');
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:customers,user_id',
            'package_id' => 'nullable|exists:packages,id',
            'simah_limit' => 'nullable|numeric|min:0',
            'limit_arabianpay_before' => 'required|numeric|min:0',
            'limit_arabianpay_after' => 'required|numeric|min:0',
            'comission' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
