<?php

namespace App\Http\Requests;

use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class StoreBulkProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('product_management');
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array'],
            'products.*.name' => ['required', 'string', 'max:255', new NoHtml],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.description' => ['nullable', 'string', new NoHtml],
            'products.*.unit' => ['required', 'string', 'max:50'],
            'products.*.stock' => ['nullable', 'integer', 'min:0'],
            'products.*.brand_id' => ['nullable', 'exists:brands,id'],
            'products.*.thumbnail' => ['nullable', 'string'],
        ];
    }
}
