<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        $request->user()->comments()->create([
            'post_id' => $post->id,
            'content' => $data['content'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function like(Comment $comment): RedirectResponse
    {
        $user = auth()->user();

        // Check if the user already liked this comment
        $existingLike = $comment->likes()->where('user_id', $user->id)->first();

        if (! $existingLike) {
            $like = new Like;
            $like->user_id = $user->id;
            $comment->likes()->save($like);
        }

        return back();
    }

    public function unlike(Comment $comment): RedirectResponse
    {
        $user = auth()->user();

        $comment->likes()->where('user_id', $user->id)->delete();

        return back();

    }
}
