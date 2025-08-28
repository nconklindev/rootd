<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Inertia\Inertia;

class FeedController extends Controller
{
    public function index()
    {
        $user = User::find(auth()->user()->id);
        $followedUserIds = $user->following->pluck('id');

        // Get all posts from followed users
        $posts = Post::with(['user:id,name,username'])
            ->whereIn('user_id', $followedUserIds)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => "post-{$post->id}",
                    'type' => 'post',
                    'user' => $post->user,
                    'timestamp' => $post->created_at,
                    'human_time' => $post->created_at->diffForHumans(),
                    'data' => $post->only(['id', 'title', 'slug', 'excerpt'])
                ];
            });

        // Get all comments from followed users
        $comments = \App\Models\Comment::with(['user:id,name,username', 'post:id,title,slug'])
            ->whereIn('user_id', $followedUserIds)
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => "comment-{$comment->id}",
                    'type' => 'comment',
                    'user' => $comment->user,
                    'timestamp' => $comment->created_at,
                    'human_time' => $comment->created_at->diffForHumans(),
                    'data' => [
                        'id' => $comment->id,
                        'body' => $comment->content,
                        'post' => $comment->post->only(['id', 'title', 'slug'])
                    ]
                ];
            });

        // Get all likes on posts from followed users
        $likes = Like::with(['user:id,name,username', 'likeable'])
            ->whereIn('user_id', $followedUserIds)
            ->where('likeable_type', Post::class)
            ->get()
            ->map(function ($like) {
                return [
                    'id' => "like-{$like->id}",
                    'type' => 'like',
                    'user' => $like->user,
                    'timestamp' => $like->created_at,
                    'human_time' => $like->created_at->diffForHumans(),
                    'data' => [
                        'id' => $like->id,
                        'post' => $like->likeable->only(['id', 'title', 'slug'])
                    ]
                ];
            });

        // Merge all activities and sort by timestamp
        $activityFeed = $posts->concat($comments)->concat($likes)
            ->sortByDesc('timestamp')
            ->values();

        return Inertia::render('Feed', [
            'activityFeed' => $activityFeed,
        ]);
    }
}
