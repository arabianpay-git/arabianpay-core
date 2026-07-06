<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'settlement_number' => $this->settlement_number,
            'supplier_user_id' => $this->supplier_user_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'settlement_date' => $this->settlement_date,
            'total_amount' => (float) $this->total_amount,
            'commission_amount' => (float) $this->commission_amount,
            'payable_amount' => (float) $this->payable_amount,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'orders_count' => $this->whenCounted('orders'),
            'supplier' => $this->whenLoaded('supplier', fn () => [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'business_name' => $this->supplier->business_name,
            ]),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'approver' => $this->whenLoaded('approver', fn () => [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ]),
            'payer' => $this->whenLoaded('payer', fn () => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
            ]),
            'payouts' => SupplierPayoutResource::collection($this->whenLoaded('payouts')),
            'orders' => $this->whenLoaded('orders', fn () => $this->orders->map(fn ($o) => [
                'id' => $o->id,
                'grand_total' => (float) $o->grand_total,
                'commission_amount' => (float) $o->commission_amount,
                'delivery_status' => $o->delivery_status,
                'delivered_at' => $o->delivered_at,
            ])),
        ];
    }
}
