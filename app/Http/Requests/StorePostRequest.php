<?php

namespace App\Http\Requests;

use App\Enum\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool)$this->user();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(PostType::class)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['string', 'max:25'],
        ];
    }
}
