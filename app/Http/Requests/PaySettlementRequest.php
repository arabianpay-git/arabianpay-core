<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for settlement payment with maker-checker authorization.
 */
class PaySettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settlement = $this->route('settlement');

        return $this->user()->can('pay', $settlement);
    }

    public function rules(): array
    {
        return [];
    }
}
