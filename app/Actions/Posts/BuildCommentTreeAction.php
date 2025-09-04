<?php

namespace App\Actions\Posts;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Collection;

class BuildCommentTreeAction
{
    public function handle(Post $post): Collection
    {
        // Load all comments for this post with their authors (include avatar for display)
        $allComments = $post->comments()
            ->with(['user:id,name,username'])
            ->withCount('likes')
            ->when(auth()->check(), function ($query) {
                $query->with(['likes' => function ($q) {
                    $q->where('user_id', auth()->id());
                }]);
            })
            ->orderBy('created_at')
            ->get();

        // Build a nested tree (children) from the flat list
        /** @var Collection<int|null, Collection<int, Comment>> $byParent */
        $byParent = $allComments->toBase()->groupBy('parent_id');

        $mapComment = function (Comment $comment) use (&$mapComment, $byParent): array {
            /** @var Collection<int, Comment> $children */
            $children = collect($byParent->get($comment->id));

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'created_at_human' => $comment->created_at_human,
                'user' => $comment->user?->only(['id', 'name', 'username', 'avatar']),
                'likes_count' => $comment->likes_count,
                'is_liked' => auth()->check() && $comment->likes->isNotEmpty(),
                'children' => $children
                    ->map(static fn (Comment $child): array => $mapComment($child))
                    ->values(),
            ];
        };

        return collect($byParent->get(null))
            ->map(static fn (Comment $c): array => $mapComment($c))
            ->values();
    }
}
