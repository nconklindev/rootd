<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Models\User;
use Inertia\Inertia;

class UserPostController extends Controller
{
    public function index(User $user)
    {
        $posts = $user->posts()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name', 'category:id,name,slug,color'])
            ->withCount('comments', 'likes')
            ->get();

        // Transform posts to include icon information
        $posts->transform(function ($post) {
            $postType = PostType::from($post->type->value);
            $post->type_icon = $postType->icon();
            $post->type_label = $postType->label();

            return $post;
        });

        return Inertia::render('UserPosts/Index', [
            'posts' => $posts,
            'user' => $user->only(['id', 'name']),
        ]);
    }
}
