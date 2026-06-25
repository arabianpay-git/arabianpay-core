<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('supplier_management');
    }

    public function rules(): array
    {
        $documentType = $this->input('document_type');

        $rules = [
            'document_type' => 'required|in:cr_file,vat_file,return_policy_file,exchange_policy_file,cancel_policy_file,id_image,balady_certificate,manager_approval,contract,iban_certificate',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
        ];

        if ($documentType === 'iban_certificate') {
            $rules['bank_name'] = 'required|string|max:255';
            $rules['account_name'] = 'required|string|max:255';
            $rules['iban'] = 'required|string|max:34';
            $rules['bank_id'] = 'required|exists:supplier_banks,id';
        }

        return $rules;
    }
}
