<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Please enter a comment before submitting.',
        ];
    }
}
