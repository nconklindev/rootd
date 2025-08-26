<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Models\Category;
use App\Models\Post;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        $posts = $category->posts()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name', 'category:id,name,slug,color'])
            ->withCount('comments', 'likes')
            ->when(auth()->check(), function ($query) {
                $query->with(['likes' => function ($q) {
                    $q->where('user_id', auth()->id());
                }]);
            })
            ->paginate(10)
            ->withQueryString();

        // Transform posts to include icon information and like status
        $posts->through(function ($post) {
            $postType = PostType::from($post->type->value);
            $post->type_icon = $postType->icon();
            $post->type_label = $postType->label();
            $post->is_liked = auth()->check() && $post->likes->isNotEmpty();

            return $post;
        });

        return Inertia::render('Categories/Show', [
            'posts' => $posts,
            'category' => $category->only(['id', 'name', 'slug', 'color']),
        ]);
    }

    public function index()
    {
        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'color', 'description'])
            ->withCount([
                'posts',
                'posts as total_comments' => function ($query) {
                    $query->join('comments', 'posts.id', '=', 'comments.post_id');
                },
                'posts as total_likes' => function ($query) {
                    $query->join('likes', function ($join) {
                        $join->on('posts.id', '=', 'likes.likeable_id')
                            ->where('likes.likeable_type', '=', Post::class);
                    });
                }
            ])
            ->orderBy('posts_count', 'desc') // Order by most posts first
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }
}
