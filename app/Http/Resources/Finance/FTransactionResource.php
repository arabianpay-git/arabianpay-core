<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'checkout_id' => $this->checkout_id,
            'customer_id' => $this->customer_id,
            'supplier_id' => $this->supplier_id,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'payment_id' => $this->payment_id,
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'transaction_date' => $this->transaction_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
            ]),
            'order' => $this->whenLoaded('order', fn () => [
                'id' => $this->order->id,
                'grand_total' => (float) $this->order->grand_total,
            ]),
            'payment' => $this->whenLoaded('payment', fn () => [
                'id' => $this->payment->id,
                'amount' => (float) $this->payment->amount,
            ]),
            'entries' => FEntryResource::collection($this->whenLoaded('entries')),
        ];
    }
}
