<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assigned_to' => $this->assigned_to,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'seller_approval' => $this->seller_approval,
            'admin_approval' => $this->admin_approval,
            'refund_amount' => (float) $this->refund_amount,
            'reason' => $this->reason,
            'reject_reason' => $this->reject_reason,
            'refund_status' => $this->refund_status,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller->id, 'name' => $this->seller->name,
            ]),
            'assigned' => $this->whenLoaded('assigned', fn () => [
                'id' => $this->assigned->id, 'name' => $this->assigned->name,
            ]),
        ];
    }
}
