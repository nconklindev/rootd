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

    protected function prepareForValidation(): void
    {
        if ($this->has('tags') && is_array($this->tags)) {
            // Filter out empty tags and trim whitespace
            $filteredTags = array_filter(
                array_map('trim', $this->tags),
                fn ($tag) => ! empty($tag)
            );

            $this->merge(['tags' => array_values($filteredTags)]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::enum(PostType::class)],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['required', 'string', 'max:25'],
        ];
    }
}
