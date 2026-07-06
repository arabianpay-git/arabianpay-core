<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'account_id' => $this->account_id,
            'debit' => (float) $this->debit,
            'credit' => (float) $this->credit,
            'entry_date' => $this->entry_date,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];

        if ($this->relationLoaded('account')) {
            $data['account'] = [
                'id' => $this->account->id,
                'account_name' => $this->account->account_name,
                'account_type1' => $this->account->account_type1,
                'account_type2' => $this->account->account_type2,
            ];
        }

        if (property_exists($this, 'running_balance')) {
            $data['running_balance'] = $this->running_balance;
        }

        return $data;
    }
}
