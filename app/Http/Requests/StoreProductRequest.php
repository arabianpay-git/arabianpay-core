<?php

namespace App\Http\Requests;

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
        return true; // You can add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_price' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpg,jpeg,png,gif',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,gif',
            'weight' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0',
            'stock_visibility_state' => 'nullable|string',
            'current_stock' => 'nullable|numeric|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'tax' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|string|max:255',
            'shipping_type' => 'nullable|string|max:255',
            'shipping_cost' => 'nullable|numeric|min:0',
            'is_quantity_multiplied' => 'nullable|boolean',
            'est_shipping_days' => 'nullable|numeric|min:0',
            'featured' => 'nullable|boolean',
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
