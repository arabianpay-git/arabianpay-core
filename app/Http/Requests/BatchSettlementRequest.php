<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for batch settlement operations.
 */
class BatchSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by route-level permission middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'settlement_ids' => 'required|array|min:1',
            'settlement_ids.*' => 'exists:settlements,id',
        ];
    }
}
