<?php

namespace App\Http\Requests;

use App\Rules\NoHtml;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()?->can('product.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255', new NoHtml], // remove regex:/^[a-zA-Z\s]*$/
            'user_id' => 'required',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'discount_type' => 'nullable',
            'discount_start_date' => 'nullable',
            'discount_end_date' => 'nullable',
            'unit' => 'required|', // remove regex:/^[a-zA-Z\s]*$/
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'short_description' => 'nullable',
            'description' => 'nullable',
            'photos' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'thumbnail' => 'nullable',
            'weight' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0',
            'stock_visibility_state' => 'nullable|string',
            'current_stock' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string',
            'low_stock_quantity' => 'nullable|numeric|min:0',
            // 'tax' => 'nullable|numeric|min:0',
            // 'tax_type' => 'nullable|string|max:255',
            'shipping_type' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable|numeric|min:0',
            'is_quantity_multiplied' => 'nullable|boolean',
            'est_shipping_days' => 'nullable|numeric|min:0',
            'featured' => 'nullable|boolean',
            'refundable' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255|', // remove regex:/^[a-zA-Z\s]*$/
            'meta_description' => 'nullable|string|max:255|', // remove regex:/^[a-zA-Z\s]*$/
            'meta_img' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Product name is required.',
            'category_id.exists' => 'The selected category is invalid.',
            'brand_id.exists' => 'The selected brand is invalid.',
            'unit_price.required' => 'Unit price is required.',
            'unit_price.numeric' => 'Unit price must be a number.',
            'photos.array' => 'Photos must be an array.',
            'photos.*.image' => 'Each photo must be an image.',
        ];
    }
}
