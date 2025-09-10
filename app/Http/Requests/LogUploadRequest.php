<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:10',
            'files.*' => [
                'required',
                'file',
                'max:204800', // 200MB max per file
                'mimetypes:text/plain,application/json,text/csv,application/octet-stream'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'files.required' => 'Please select at least one log file to upload.',
            'files.max' => 'You can upload a maximum of 10 files at once.',
            'files.*.required' => 'Each file is required.',
            'files.*.file' => 'Each upload must be a valid file.',
            'files.*.max' => 'Each file must be no larger than 200MB.',
            'files.*.mimetypes' => 'Only log files (.log, .txt, .json, .csv) are allowed.',
        ];
    }
}
