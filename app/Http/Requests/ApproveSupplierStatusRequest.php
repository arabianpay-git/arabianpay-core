<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSupplierStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('supplier_management');
    }

    public function rules(): array
    {
        return [
            'commission' => 'required|numeric|min:0|max:100',
            'reason' => 'nullable|string|max:1000',
            'contract' => 'required|file|mimes:pdf,jpg,jpeg,png',
            'payment_schedule' => 'required|integer|min:0|max:100',
            'contract_end_date' => 'required|date',
        ];
    }
}
