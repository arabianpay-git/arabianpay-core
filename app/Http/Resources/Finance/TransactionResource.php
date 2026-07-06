<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'plan_id' => $this->plan_id,
            'loan_amount' => (float) $this->loan_amount,
            'collected' => (float) $this->collected,
            'retrieved' => (float) $this->retrieved,
            'canceled' => (float) $this->canceled,
            'subscription_fees' => (float) $this->subscription_fees,
            'payment_status' => $this->payment_status,
            'settlement_status' => $this->settlement_status,
            'general_status' => $this->general_status,
            'loan_start_date' => $this->loan_start_date,
            'loan_end_date' => $this->loan_end_date,
            'credit_limit_at_time' => (float) $this->credit_limit_at_time,
            'remaining_credit_limit' => (float) $this->remaining_credit_limit,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id, 'name' => $this->seller->name,
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id, 'grand_total' => (float) $this->order->grand_total,
            ]),
        ];
    }
}
