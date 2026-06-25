<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('transaction_references');
    }

    public function rules(): array
    {
        return [
            'settlement_ids' => 'required|array',
            'settlement_ids.*' => 'exists:settlements,id',
        ];
    }
}
