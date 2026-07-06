<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'schedule_payment_id' => $this->schedule_payment_id,
            'checkout_id' => $this->checkout_id,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'amount' => (float) $this->amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'invoice_number' => $this->invoice_number,
            'txn_code' => $this->txn_code,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
        ];
    }
}
