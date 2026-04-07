<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for updating customer credit limits.
 */
class UpdateCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('credit-limit.update');
    }

    public function rules(): array
    {
        return [
            'credit_limit_id' => 'required|exists:customer_credit_limits,id',
            'limit_arabianpay_before' => 'required|numeric|min:0',
            'limit_arabianpay_after' => 'required|numeric|min:0',
            'comission' => 'nullable|numeric|min:0|max:100',
            'reason' => 'required|string|max:500',
        ];
    }
}
