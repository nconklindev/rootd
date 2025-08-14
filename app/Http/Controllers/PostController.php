<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->where('status', 'published')
            ->latest('id')
            ->select(['id', 'slug', 'excerpt', 'type', 'status', 'user_id'])
            ->with(['user:id,name'])
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'can' => [
                'create' => (bool) $request->user(),
            ],
        ]);
    }

    public function show(Post $post): Response
    {
        // Allow viewing drafts only by owner; published are public
        if ($post->status !== 'published') {
            $this->authorize('view', $post);
        }

        return Inertia::render('Posts/Show', [
            'post' => $post->only(['id', 'slug', 'content', 'excerpt', 'type', 'status']) + [
                'author' => $post->user?->only(['id', 'name']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('Posts/Create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();

        $post = $request->user()->posts()->create([
            'slug' => $data['slug'],
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? null,
            'type' => $data['type'] ?? 'article',
            'status' => $data['status'] ?? 'draft',
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        return Inertia::render('Posts/Edit', [
            'post' => $post->only(['slug', 'content', 'excerpt', 'type', 'status']),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $data = $request->validated();

        $post->update([
            'slug' => $data['slug'],
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? null,
            'type' => $data['type'] ?? 'article',
            'status' => $data['status'] ?? 'draft',
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }
}
