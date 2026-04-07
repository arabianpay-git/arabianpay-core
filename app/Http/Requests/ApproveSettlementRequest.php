<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * [PHASE-1] FormRequest for settlement approval with maker-checker authorization.
 */
class ApproveSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $settlement = $this->route('settlement');

        return $this->user()->can('approve', $settlement);
    }

    public function rules(): array
    {
        return [];
    }
}
