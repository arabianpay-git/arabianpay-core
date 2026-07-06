<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvestmentPoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'expected_collections' => (float) $this->expected_collections,
            'status' => $this->status,
            'total_checkouts' => $this->total_checkouts,
            'total_disbursed' => (float) $this->total_disbursed,
            'total_collected' => (float) $this->total_collected,
            'collection_rate' => (float) $this->collection_rate,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'days_remaining' => $this->when($this->end_date, fn () => $this->end_date->diffInDays(now(), false)),
            'is_active' => $this->status === 'active',
        ];
    }
}
