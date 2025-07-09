<?php

namespace App\Http\Requests;

use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name.en' => ['required', 'string', 'max:255', new NoHtml],
            'name.ar' => ['nullable', 'string', 'max:255', new NoHtml],

            'unit.en' => 'nullable|string|max:255',

            'tags.en' => 'nullable|array',
            'tags.en.*' => 'nullable|max:255',

            'tags.ar' => 'nullable|array',
            'tags.ar.*' => 'nullable|max:255',

            'user_id' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'nullable',
            'discount_start_date' => 'nullable',
            'discount_end_date' => 'nullable',
            'photos' => 'nullable|array',
            'thumbnail' => 'nullable|string|max:255',
            'weight' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0',
            'stock_visibility_state' => 'nullable|string',
            'current_stock' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string',
            'low_stock_quantity' => 'nullable|numeric|min:0',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable|max:255',
            'meta_img' => 'nullable|max:255',
            'tax' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|string|max:255',
            'shipping_type' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable|numeric|min:0',
            'is_quantity_multiplied' => 'nullable|boolean',
            'est_shipping_days' => 'nullable|numeric|min:0',
            'featured' => 'nullable|boolean',
            'refundable' => 'nullable|boolean',

            'published' => 'nullable',
            'approved' => 'nullable',
            'reason_reject' => $this->input('approved') === 'rejected' ? 'required|string|max:255' : 'nullable',
        ];
    }

    public function messages()
    {
        return [
            'name.en.required' => 'Product name (English) is required.',
            'name.ar.required' => 'Product name (Arabic) is required.',
            'tags.en.*.max' => 'Each English tag must not exceed 255 characters.',
            'tags.ar.*.max' => 'Each Arabic tag must not exceed 255 characters.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'unit_price.required' => 'Unit price is required.',
            'unit_price.numeric' => 'Unit price must be a number.',
        ];
    }
}
