<?php

namespace App\Actions\Posts;

use App\Enum\PostType;
use Illuminate\Pagination\LengthAwarePaginator;

class TransformPostAction
{
    public function handle(LengthAwarePaginator $posts): LengthAwarePaginator
    {
        return $posts->through(function ($post) {
            $postType = PostType::from($post->type->value);
            $post->type_icon = $postType->icon();
            $post->type_label = $postType->label();
            $post->is_liked = auth()->check() && $post->likes->isNotEmpty();

            return $post;
        });
    }
}
