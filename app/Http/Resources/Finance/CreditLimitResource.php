<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditLimitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'package_id' => $this->package_id,
            'simah_limit' => (float) $this->simah_limit,
            'limit_arabianpay_after' => (float) $this->limit_arabianpay_after,
            'limit_arabianpay_before' => (float) $this->limit_arabianpay_before,
            'comission' => (float) $this->comission,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
        ];
    }
}
