<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('supplier_management');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:under_review,active,contract_sent,approved,suspended,pending,blacklisted',
        ];
    }
}
