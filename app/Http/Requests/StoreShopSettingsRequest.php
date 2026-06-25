<?php

namespace App\Http\Requests;

use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class StoreShopSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('shop_settings_management');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new NoHtml],
            'logo' => 'nullable|string',
            'sliders' => 'nullable|array',
            'sliders.*' => ['nullable', 'string', new NoHtml],
            'banner' => 'nullable|string',
            'phone_number' => ['required', 'regex:/^(?:\+9665\d{8}|05\d{8})$/', 'string'],
            'address' => ['required', 'string', 'max:255', new NoHtml],
        ];
    }
}
