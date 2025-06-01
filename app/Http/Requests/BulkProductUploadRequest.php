<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkProductUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt,xlsx|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a CSV, TXT, or XLSX file.',
            'file.mimes' => 'Only CSV, TXT, or XLSX files are allowed.',
            'file.max' => 'The file size must not exceed 2 MB.',
        ];
    }
}
