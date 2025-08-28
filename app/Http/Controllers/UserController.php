<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(User $user)
    {
        // Get basic user stats (public information only)
        $userStats = [
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'total_posts' => $user->posts()->count(),
            'total_views' => $user->posts()->sum('views_count'),
            'total_likes_received' => Like::whereIn('likeable_id', $user->posts()->pluck('id'))
                    ->where('likeable_type', Post::class)
                    ->count() +
                Like::whereIn('likeable_id', $user->comments()->pluck('id'))
                    ->where('likeable_type', Comment::class)
                    ->count(),
        ];

        // Get user's recent posts (public)
        $recentPosts = $user->posts()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name', 'category:id,name,slug,color'])
            ->withCount('comments', 'likes')
            ->take(6)
            ->get()
            ->transform(function ($post) {
                $postType = PostType::from($post->type->value);
                $post->type_icon = $postType->icon();
                $post->type_label = $postType->label();

                return $post;
            });

        // Get user's top posts by engagement
        $topPosts = $user->posts()
            ->withCount(['likes', 'comments'])
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'views_count' => $post->views_count,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->comments_count,
                    'engagement_score' => ($post->likes_count * 2) + $post->comments_count + $post->views_count,
                    'created_at' => $post->created_at,
                ];
            })
            ->sortByDesc('engagement_score')
            ->take(3)
            ->values();

        // Category stats for user's posts
        $categoryStats = $user->posts()
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.slug', 'categories.color')
            ->selectRaw('COUNT(posts.id) as posts_count')
            ->selectRaw('SUM(posts.views_count) as total_views')
            ->groupBy('categories.id', 'categories.name', 'categories.slug', 'categories.color')
            ->orderByDesc('posts_count')
            ->take(5)
            ->get();

        // Recent activity timeline (last 30 days, public activities only)
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $recentActivity = $user->posts()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($post) {
                return [
                    'type' => 'post',
                    'action' => 'created',
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'created_at' => $post->created_at,
                ];
            });

        return Inertia::render('User/Show', [
            'profileUser' => $user->only(['id', 'name', 'username']),
            'userStats' => $userStats,
            'recentPosts' => $recentPosts,
            'topPosts' => $topPosts,
            'categoryStats' => $categoryStats,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function follow(User $user)
    {
        auth()->user()->following()->toggle($user->id);
        session()->flash('success', 'User followed successfully.');
        return back();
    }
}
