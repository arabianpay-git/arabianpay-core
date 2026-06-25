<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return hasSensitivePermission('media_management');
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array',
            'files.*' => [
                'file',
                'mimes:jpeg,png,jpg,webp,gif,pdf,mp4,mov,avi,mkv',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Please select at least one file to upload.',
            'files.*.file' => 'Each item must be a valid file.',
            'files.*.mimes' => 'Only JPEG, PNG, JPG, WEBP, GIF, PDF, and video files (MP4, MOV, AVI, MKV) are allowed.',
            'files.*.max' => 'Video files must not be larger than 5MB.',
        ];
    }
}
