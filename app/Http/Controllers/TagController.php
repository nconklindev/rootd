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
            ->paginate(15);

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

    public function show()
    {
        //
    }
}
