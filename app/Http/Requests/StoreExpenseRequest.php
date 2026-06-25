<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('expense_management');
    }

    public function rules(): array
    {
        return [
            'refrence_id' => 'required|string|max:255|unique:expense_setting,refrence_id',
            'description' => 'required|string|max:255',
            'amount_type' => 'required|in:fixed,percent',
            'amount' => [
                'required',
                'numeric',
                'min:0',
                $this->input('amount_type') === 'percent' ? 'max:100' : '',
            ],
            'credit_acc_id' => 'required|exists:f_accounts,id',
        ];
    }
}
