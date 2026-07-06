<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'schedule_payment_id' => $this->schedule_payment_id,
            'user_id' => $this->user_id,
            'assigned_to' => $this->assigned_to,
            'claim_type' => $this->claim_type,
            'claim_status' => $this->claim_status,
            'priority' => $this->priority,
            'notes' => $this->notes,
            'contact_method' => $this->contact_method,
            'attempted_at' => $this->attempted_at,
            'contacted_at' => $this->contacted_at,
            'promised_payment_date' => $this->promised_payment_date,
            'customer_response' => $this->customer_response,
            'promised_amount' => (float) $this->promised_amount,
            'next_follow_up' => $this->next_follow_up,
            'requires_escalation' => $this->requires_escalation,
            'escalation_reason' => $this->escalation_reason,
            'attempt_count' => $this->attempt_count,
            'communication_log' => $this->communication_log,
            'created_at' => $this->created_at,
            'schedule_payment' => $this->whenLoaded('schedulePayment', fn () => [
                'id' => $this->schedulePayment->id,
                'instalment_number' => $this->schedulePayment->instalment_number,
                'instalment_amount' => (float) $this->schedulePayment->instalment_amount,
                'payment_status' => $this->schedulePayment->payment_status,
                'due_date' => $this->schedulePayment->due_date,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id, 'name' => $this->user->name,
            ]),
            'assigned_to_user' => $this->whenLoaded('assignedTo', fn () => [
                'id' => $this->assignedTo->id, 'name' => $this->assignedTo->name,
            ]),
        ];
    }
}
