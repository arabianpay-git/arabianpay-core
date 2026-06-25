<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('transfer_models');
    }

    public function rules(): array
    {
        return [
            'to_user_id' => 'required|exists:users,id',
            'model_type' => 'required|in:App\Models\Order,App\Models\Merchant,App\Models\SupportTicket,App\Models\SchedulePayment,App\Models\RefundRequest,App\Models\Transaction,App\Models\Customer',
            'model_id' => 'required|integer',
            'description' => 'nullable|string',
        ];
    }
}
