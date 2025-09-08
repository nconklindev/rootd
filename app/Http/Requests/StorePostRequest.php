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
            'link' => ['nullable', 'url', 'max:255'],
            'type' => ['nullable', Rule::enum(PostType::class)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tags' => ['nullable', 'array', 'max:5'],
            'tags.*' => ['required', 'string', 'max:25'],
            'file' => [
                'nullable',
                'required_if:type,media',
                'file',
                'max:15360', // 15MB in kilobytes
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,txt,py,bat,sh,rb,php,js,ts,json,zip,rar,7z,tif,rs,cs,c,go,lua,sql,perl,java,kotlin,asm,xml,ps,ps1',
            ],
        ];
    }
}
