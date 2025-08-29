<?php

namespace App\Http\Controllers;

use App\Enum\PostType;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
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
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name,username', 'category:id,name,slug,color', 'tags:id,name,slug,color'])
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

        $post->load(['user:id,name,username', 'tags']);

        // Load post likes data for the current user
        $post->loadCount('likes');
        if (auth()->check()) {
            $post->load(['likes' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }

        // Load all comments for this post with their authors (include avatar for display)
        $allComments = $post->comments()
            ->with(['user:id,name,username'])
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
                'user' => $comment->user?->only(['id', 'name', 'username', 'avatar']),
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
            'post' => $post->only(['id', 'title', 'slug', 'content', 'body', 'excerpt', 'type', 'views_count', 'created_at']) + [
                    'author' => $post->user?->only(['id', 'name']),
                    'comments' => $rootComments,
                    'tags' => $post->tags->map(fn($tag) => $tag->only(['name', 'color']))->toArray(),
                    'likes_count' => $post->likes_count,
                    'is_liked' => auth()->check() && $post->likes->isNotEmpty(),
                ],
            'title' => $title,
        ]);
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->authorize('create', Post::class);

        // Create a new Post instance and fill it with validated data
        $post = new Post();
        $post->fill($request->validated());
        $post->user_id = $request->user()->id;

        $post->save();

        // Handle category association
        if (!empty($request->validated()['category_id'])) {
            $post->category()->associate($request->validated()['category_id']);
            $post->save();
        }

        // Handle tags
        $tagNames = $request->validated()['tags'] ?? [];
        if (!empty($tagNames)) {
            $tagIds = $this->createOrFindTags($tagNames);
            $post->tags()->attach($tagIds);
        }

        return to_route('posts.show', $post);
    }

    /**
     * Create or find tags by name and return their IDs.
     *
     * @param array<string> $tagNames
     * @return array<int>
     */
    private function createOrFindTags(array $tagNames): array
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            // Clean up the tag name
            $cleanName = trim($tagName);
            if (empty($cleanName)) {
                continue;
            }

            // Find existing tag or create new one
            $tag = Tag::firstOrCreate(
                ['name' => $cleanName],
                [
                    'name' => $cleanName,
                    'slug' => Str::slug($cleanName),
                    'color' => $this->generateTagColor(),
                    'description' => '', // Empty description for auto-created tags
                ]
            );

            $tagIds[] = $tag->id;
        }

        return $tagIds;
    }

    /**
     * Generate a random color with good contrast for tag display.
     * Uses a curated list of colors that work well with light backgrounds.
     */
    private function generateTagColor(): string
    {
        $colors = [
            '#3B82F6', // Blue
            '#10B981', // Emerald
            '#8B5CF6', // Violet
            '#F59E0B', // Amber
            '#EF4444', // Red
            '#06B6D4', // Cyan
            '#84CC16', // Lime
            '#F97316', // Orange
            '#EC4899', // Pink
            '#6366F1', // Indigo
            '#14B8A6', // Teal
            '#A855F7', // Purple
            '#DC2626', // Red-600
            '#059669', // Emerald-600
            '#7C3AED', // Violet-600
            '#D97706', // Amber-600
            '#0891B2', // Cyan-600
            '#65A30D', // Lime-600
            '#EA580C', // Orange-600
            '#BE185D', // Pink-600
        ];

        return $colors[array_rand($colors)];
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('Posts/Create', [
            'postTypes' => collect(PostType::cases())->map(fn($type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ])->sortBy('label')->values(),
            'categories' => Category::select(['id', 'name', 'slug', 'color'])
                ->orderBy('name')
                ->get()
                ->map(fn($category) => [
                    'value' => $category->id,
                    'label' => $category->name,
                    'slug' => $category->slug,
                    'color' => $category->color,
                ]),
        ]);
    }

    public function edit(Post $post): Response
    {
        $this->authorize('update', $post);

        // Load existing tags for the post
        $post->load('tags');

        return Inertia::render('Posts/Edit', [
            'post' => $post->only(['title', 'slug', 'content', 'excerpt', 'type']) + [
                    'tags' => $post->tags->pluck('name')->toArray(),
                ],
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
            'body' => $data['body'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'type' => $data['type'] ?? 'article',
        ]);

        // Handle tags - sync existing tags with new ones
        $tagNames = $data['tags'] ?? [];
        if (!empty($tagNames)) {
            $tagIds = $this->createOrFindTags($tagNames);
            $post->tags()->sync($tagIds); // sync() replaces all current tags
        } else {
            $post->tags()->detach(); // Remove all tags if none provided
        }

        return redirect()->route('posts.show', $post);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('posts.index');
    }

    public function myPosts(): Response
    {
        $posts = auth()->user()->posts()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name,username', 'category:id,name,slug,color'])
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

        return Inertia::render('Posts/MyPosts', [
            'posts' => $posts,
            'user' => auth()->user()->only(['id', 'name']),
        ]);
    }

    public function like(int $id): RedirectResponse
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        // Check if the user already liked this post
        $existingLike = $post->likes()->where('user_id', $user->id)->first();

        if (!$existingLike) {
            $like = new Like;
            $like->user_id = $user->id;
            $post->likes()->save($like);
        }

        return back();
    }

    public function unlike(int $id): RedirectResponse
    {
        $post = Post::findOrFail($id);
        $user = auth()->user();

        $post->likes()->where('user_id', $user->id)->delete();

        return back();
    }
}
