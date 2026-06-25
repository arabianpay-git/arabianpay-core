<?php

namespace App\Http\Requests;

use App\Models\RefundRequest;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRefundStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $refundRequest = RefundRequest::find($this->route('id'));

        return $refundRequest && $this->user()->can('approve', $refundRequest);
    }

    public function rules(): array
    {
        return [
            'refund_status' => 'required|in:pending,approved,rejected',
        ];
    }
}
