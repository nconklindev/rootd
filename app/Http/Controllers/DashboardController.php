<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get time ranges for analytics
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $today = Carbon::now()->startOfDay();

        // User's content statistics
        $userStats = [
            'total_followers' => $user->followers()->count(),
            'total_following' => $user->following()->count(),
            'total_posts' => $user->posts()->count(),
            'total_comments' => $user->comments()->count(),
            'total_likes_given' => Like::where('user_id', $user->id)->count(),
            'total_likes_received' => Like::whereIn('likeable_id', $user->posts()->pluck('id'))
                ->where('likeable_type', Post::class)
                ->count() +
                Like::whereIn('likeable_id', $user->comments()->pluck('id'))
                    ->where('likeable_type', Comment::class)
                    ->count(),
            'total_views' => $user->posts()->sum('views_count'),
        ];

        // Recent activity (last 30 days)
        $recentActivity = [
            'posts_this_month' => $user->posts()->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'posts_this_week' => $user->posts()->where('created_at', '>=', $sevenDaysAgo)->count(),
            'comments_this_month' => $user->comments()->where('created_at', '>=', $thirtyDaysAgo)->count(),
            'comments_this_week' => $user->comments()->where('created_at', '>=', $sevenDaysAgo)->count(),
        ];

        // Top performing posts
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
            ->take(5)
            ->values();

        // Recent activity timeline (last 2 weeks)
        $activityTimeline = $this->getRecentActivityTimeline($user);

        // Category performance for user's posts
        $categoryStats = $user->posts()
            ->join('categories', 'posts.category_id', '=', 'categories.id')
            ->select('categories.name', 'categories.slug', 'categories.color')
            ->selectRaw('COUNT(posts.id) as posts_count')
            ->selectRaw('SUM(posts.views_count) as total_views')
            ->groupBy('categories.id', 'categories.name', 'categories.slug', 'categories.color')
            ->orderByDesc('posts_count')
            ->get();

        // Engagement trends (daily data for last 30 days)
        $engagementTrends = $this->getEngagementTrends($user, $thirtyDaysAgo);

        // Community insights
        $communityInsights = [
            'total_users' => User::count(),
            'total_posts' => Post::count(),
            'total_categories' => Category::count(),
            'active_users_this_week' => User::whereHas('posts', function ($query) use ($sevenDaysAgo) {
                $query->where('created_at', '>=', $sevenDaysAgo);
            })->orWhereHas('comments', function ($query) use ($sevenDaysAgo) {
                $query->where('created_at', '>=', $sevenDaysAgo);
            })->count(),
        ];

        return Inertia::render('Dashboard', [
            'userStats' => $userStats,
            'recentActivity' => $recentActivity,
            'topPosts' => $topPosts,
            'activityTimeline' => $activityTimeline,
            'categoryStats' => $categoryStats,
            'engagementTrends' => $engagementTrends,
            'communityInsights' => $communityInsights,
        ]);
    }

    private function getRecentActivityTimeline(User $user): array
    {
        $twoWeeksAgo = Carbon::now()->subWeeks(2);

        // Get user's recent posts
        $recentPosts = $user->posts()
            ->where('created_at', '>=', $twoWeeksAgo)
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

        // Get user's recent comments
        $recentComments = $user->comments()
            ->with('post:id,title,slug')
            ->where('created_at', '>=', $twoWeeksAgo)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($comment) {
                return [
                    'type' => 'comment',
                    'action' => 'commented on',
                    'title' => $comment->post->title,
                    'slug' => $comment->post->slug,
                    'content' => str($comment->content)->limit(100),
                    'created_at' => $comment->created_at,
                ];
            });

        // Get likes user received on their content
        $recentLikes = Like::whereIn('likeable_id', $user->posts()->pluck('id'))
            ->where('likeable_type', Post::class)
            ->where('created_at', '>=', $twoWeeksAgo)
            ->with(['likeable:id,title,slug', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($like) {
                return [
                    'type' => 'like_received',
                    'action' => 'liked your post',
                    'title' => $like->likeable->title,
                    'slug' => $like->likeable->slug,
                    'user_name' => $like->user->name,
                    'created_at' => $like->created_at,
                ];
            });

        // Merge and sort all activities
        return collect()
            ->concat($recentPosts)
            ->concat($recentComments)
            ->concat($recentLikes)
            ->sortByDesc('created_at')
            ->take(15)
            ->values()
            ->toArray();
    }

    private function getEngagementTrends(User $user, Carbon $startDate): array
    {
        $trends = [];
        $currentDate = $startDate->copy();
        $endDate = Carbon::now();

        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $trends[] = [
                'date' => $currentDate->format('Y-m-d'),
                'posts' => $user->posts()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'comments' => $user->comments()->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'likes_received' => Like::whereIn('likeable_id', $user->posts()->pluck('id'))
                    ->where('likeable_type', Post::class)
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count(),
                'views' => $user->posts()
                    ->whereBetween('updated_at', [$dayStart, $dayEnd])
                    ->sum('views_count') ?: 0,
            ];

            $currentDate->addDay();
        }

        return $trends;
    }
}
