<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_user_id' => $this->from_user_id,
            'to_user_id' => $this->to_user_id,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'from_user' => $this->whenLoaded('fromUser', fn () => [
                'id' => $this->fromUser->id, 'name' => $this->fromUser->name,
            ]),
            'to_user' => $this->whenLoaded('toUser', fn () => [
                'id' => $this->toUser->id, 'name' => $this->toUser->name,
            ]),
        ];
    }
}
