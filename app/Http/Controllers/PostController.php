<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\PostViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    public function __construct(
        private readonly PostViewService $postViewService
    )
    {
    }

    public function index(Request $request): Response
    {
        $posts = Post::query()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'created_at', 'views_count'])
            ->with(['user:id,name'])
            ->withCount('comments', 'likes')
            ->paginate(10)
            ->withQueryString();

        // Transform posts to include icon information
        $posts->through(function ($post) {
            $postType = PostType::from($post->type->value);
            $post->type_icon = $postType->icon();
            $post->type_label = $postType->label();
            return $post;
        });

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'can' => [
                'create' => (bool)$request->user(),
            ],
        ]);
    }

    public function show(Post $post): Response
    {
        // Authorize viewing
        $this->authorize('view', $post);

        // Record the view (this will handle duplicate prevention)
        $this->postViewService->recordView($post);

        $post->load(['user:id,name']);

        // Load all comments for this post with their authors (include avatar for display)
        $allComments = $post->comments()
            ->with(['user:id,name'])
            ->withCount('likes')
            ->when(auth()->check(), function ($query) {
                $query->with(['likes' => function ($q) {
                    $q->where('user_id', auth()->id());
                }]);
            })
            ->orderBy('created_at')
            ->get();

        // Build a nested tree (children) from the flat list
        /** @var Collection<int|null, Collection<int, Comment>> $byParent */
        $byParent = $allComments->toBase()->groupBy('parent_id'); // Use key string instead of Closure to satisfy analyzers

        $mapComment = function (Comment $comment) use (&$mapComment, $byParent): array {
            /** @var Collection<int, Comment> $children */
            $children = collect($byParent->get($comment->id)); // Wrap in collect to satisfy analyzers

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at,
                'created_at_human' => $comment->created_at_human,
                'user' => $comment->user?->only(['id', 'name', 'avatar']),
                'likes_count' => $comment->likes_count,
                'is_liked' => auth()->check() && $comment->likes->isNotEmpty(),
                'children' => $children
                    ->map(static fn(Comment $child): array => $mapComment($child))
                    ->values(),
            ];
        };

        $rootComments = collect($byParent->get(null))
            ->map(static fn(Comment $c): array => $mapComment($c))
            ->values();

        $title = $post->title ?? Str::headline($post->slug);

        return Inertia::render('Posts/Show', [
            'post' => $post->only(['id', 'title', 'slug', 'content', 'body', 'excerpt', 'type', 'views_count']) + [
                    'author' => $post->user?->only(['id', 'name']),
                    'comments' => $rootComments,
                ],
            'title' => $title,
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();

        $post = $request->user()->posts()->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'body' => $data['body'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'type' => $data['type'] ?? 'article',
        ]);

        return redirect()->route('posts.show', $post);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('Posts/Create', [
            'postTypes' => collect(PostType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ])->sortBy('label')->values(),
        ]);
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        return Inertia::render('Posts/Edit', [
            'post' => $post->only(['title', 'slug', 'content', 'excerpt', 'type']),
            'postTypes' => collect(PostType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ])->sortBy('label')->values(),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $data = $request->validated();

        $post->update([
            'title' => $data['title'],
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? null,
            'type' => $data['type'] ?? 'article',
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
