<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('transaction_references');
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:255',
        ];
    }
}
