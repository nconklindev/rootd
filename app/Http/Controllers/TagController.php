<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        // Paginated tags list for "All Tags" section
        $tags = Tag::query()
            ->withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->orderBy('name')
            ->paginate(10);

        // Popular Tags stats
        $popularTags = Tag::withCount('posts')->orderBy('posts_count', 'desc')->limit(10)->get();

        // Summary Stats
        $allTagsCount = Tag::all()->count();

        $totalPosts = request()->get('page', 1) == 1
            ? Tag::withCount('posts')->get()->sum('posts_count')
            : null;

        // Return
        return Inertia::render('Tags/Index', [
            'tags' => Inertia::scroll($tags), // Paginated above so no need for paginate here
            'popularTags' => $popularTags,
            'allTagsCount' => $allTagsCount,
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
