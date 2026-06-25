<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('brand_management');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'order_level' => ['required', 'integer'],
            'meta_title' => ['nullable', 'string', 'min:5', 'max:100'],
            'meta_description' => ['nullable', 'string', 'min:10', 'max:255'],
        ];
    }
}
