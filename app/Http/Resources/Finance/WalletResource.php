<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'order_id' => $this->order_id,
            'instalment_id' => $this->instalment_id,
            'transaction_type' => $this->transaction_type,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
