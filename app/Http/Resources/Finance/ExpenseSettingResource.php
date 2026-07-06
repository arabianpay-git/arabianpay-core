<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refrence_id' => $this->refrence_id,
            'description' => $this->description,
            'amount_type' => $this->amount_type,
            'amount' => (float) $this->amount,
            'credit_acc_id' => $this->credit_acc_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'credit_account' => $this->whenLoaded('creditAccount', fn () => [
                'id' => $this->creditAccount->id,
                'account_name' => $this->creditAccount->account_name,
            ]),
        ];
    }
}
