<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name.en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'meta_title.en' => ['nullable', 'string', 'max:255'],
            'meta_description.en' => ['nullable', 'string', 'max:1000'],
            'unit' => ['nullable', 'array'],
            'order_level' => ['required', 'numeric'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
