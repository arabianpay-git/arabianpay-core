<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('credit_decision_output');
    }

    public function rules(): array
    {
        return [
            'credit_limit' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ];
    }
}
