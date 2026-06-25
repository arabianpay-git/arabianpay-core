<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('order_acceptance');
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'invoice_number' => 'required|string|max:255',
            'estimated_delivery_date' => 'required|date',
            'invoice_file' => 'required|file|mimes:pdf|max:2048',
        ];
    }
}
