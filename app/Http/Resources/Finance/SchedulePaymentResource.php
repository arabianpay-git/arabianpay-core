<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchedulePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'assigned_to' => $this->assigned_to,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'seller_id' => $this->seller_id,
            'checkout_id' => $this->checkout_id,
            'instalment_number' => $this->instalment_number,
            'due_date' => $this->due_date,
            'instalment_amount' => (float) $this->instalment_amount,
            'principle_amount' => (float) $this->principle_amount,
            'late_fee' => (float) $this->late_fee,
            'subscription_fee' => (float) $this->subscription_fee,
            'shipping_amount' => (float) $this->shipping_amount,
            'additional_amount' => (float) $this->additional_amount,
            'difference_amount' => (float) $this->difference_amount,
            'deducted_amount' => (float) $this->deducted_amount,
            'is_late' => $this->is_late,
            'late_days' => $this->late_days,
            'payment_status' => $this->payment_status,
            'failure_reason' => $this->failure_reason,
            'receipt' => $this->receipt,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->paid_at,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
            'assigned' => $this->whenLoaded('assigned', fn () => [
                'id' => $this->assigned->id, 'name' => $this->assigned->name,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id, 'amount' => (float) $this->payment->amount, 'payment_status' => $this->payment->payment_status,
            ]),
        ];
    }
}
