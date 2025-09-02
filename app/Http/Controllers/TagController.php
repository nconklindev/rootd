<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::query()
            ->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->orderBy('name')
            ->paginate(30);

        // Get stats for the initial page load only
        $totalTags = request()->get('page', 1) == 1
            ? Tag::count()
            : null;

        $allTagsCount = Tag::all()->count();

        $totalPosts = request()->get('page', 1) == 1
            ? Tag::withCount('posts')->get()->sum('posts_count')
            : null;

        return Inertia::render('Tags/Index', [
            'tags' => Inertia::merge($tags->items()), // Merge the tags with the pagination data
            'pagination' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'has_more_pages' => $tags->hasMorePages(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ],
            'allTagsCount' => $allTagsCount,
            'totalTags' => $totalTags,
            'totalPosts' => $totalPosts,
        ]);
    }

    public function show(Tag $tag)
    {
        $tag = Tag::with([
            'posts' => function ($query) {
                $query->with(['user:id,name,username'])
                    ->withCount(['comments', 'likes'])
                    ->latest()
                    ->limit(10);
            },
        ])
            ->withCount('posts')
            ->find($tag->id);

        return Inertia::render('Tags/Show', [
            'tag' => $tag,
        ]);
    }
}
