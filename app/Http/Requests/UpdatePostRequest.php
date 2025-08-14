<?php

namespace App\Http\Requests;

use App\Enum\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'slug' => [
                'required',
                'alpha_dash',
                'max:255',
                Rule::unique('posts', 'slug')->ignore($post?->id),
            ],
            'content' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'type' => ['nullable', Rule::enum(PostType::class)],
            'status' => ['nullable', 'in:draft,published'],
        ];
    }
}
