<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Number;
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
                $post->created_at_human = $post->created_at->diffForHumans();
                $post->views_count_formatted = Number::abbreviate($post->views_count);

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
                    'views_count_formatted' => Number::abbreviate($post->views_count),
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
            ->get()
            ->map(function ($category) {
                $category->total_views_formatted = Number::abbreviate($category->total_views ?? 0);
                return $category;
            });

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
                    'created_at_human' => $post->created_at->diffForHumans(),
                ];
            });

        // Format user stats
        $formattedUserStats = [
            'followers_count' => $userStats['followers_count'],
            'followers_count_formatted' => Number::abbreviate($userStats['followers_count']),
            'following_count' => $userStats['following_count'],
            'following_count_formatted' => Number::abbreviate($userStats['following_count']),
            'total_posts' => $userStats['total_posts'],
            'total_posts_formatted' => Number::abbreviate($userStats['total_posts']),
            'total_views' => $userStats['total_views'],
            'total_views_formatted' => Number::abbreviate($userStats['total_views']),
            'total_likes_received' => $userStats['total_likes_received'],
            'total_likes_received_formatted' => Number::abbreviate($userStats['total_likes_received']),
        ];

        return Inertia::render('User/Show', [
            'profileUser' => $user->only(['id', 'name', 'username']),
            'userStats' => $formattedUserStats,
            'recentPosts' => $recentPosts,
            'topPosts' => $topPosts,
            'categoryStats' => $categoryStats,
            'recentActivity' => $recentActivity,
            'isFollowing' => auth()->check() ? auth()->user()->isFollowingUser($user) : false,
        ]);
    }

    public function follow(User $user)
    {
        auth()->user()->following()->toggle($user->id);
        session()->flash('success', 'User followed successfully.');
        return back();
    }
}
