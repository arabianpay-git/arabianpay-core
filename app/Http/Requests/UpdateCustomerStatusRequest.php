<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('customers.manage');
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,suspended,pending,blacklisted',
        ];
    }
}
