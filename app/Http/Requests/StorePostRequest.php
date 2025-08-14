<?php

namespace App\Http\Requests;

use App\Enum\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'alpha_dash', 'max:255', 'unique:posts,slug'],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', Rule::enum(PostType::class)],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Please provide a unique slug for your post.',
        ];
    }
}
