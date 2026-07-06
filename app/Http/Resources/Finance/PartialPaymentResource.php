<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartialPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'employee_id' => $this->employee_id,
            'schedule_payment_id' => $this->schedule_payment_id,
            'partial_amount' => (float) $this->partial_amount,
            'partial_due_date' => $this->partial_due_date,
            'details' => $this->details,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'receipt' => $this->receipt,
            'created_at' => $this->created_at,
        ];
    }
}
