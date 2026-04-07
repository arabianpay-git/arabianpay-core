<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for settlement cancellation.
 */
class CancelSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settlement.cancel');
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:255',
        ];
    }
}
