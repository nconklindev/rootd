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
    ) {}

    public function index(Request $request): Response
    {
        $posts = Post::query()
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

        return Inertia::render('Posts/Index', [
            'posts' => $posts,
            'can' => [
                'create' => (bool) $request->user(),
            ],
        ]);
    }

    public function show(Post $post): Response
    {
        // Authorize viewing
        $this->authorize('view', $post);

        // Record the view (this will handle duplicate prevention)
        $this->postViewService->recordView($post);

        $post->load(['user:id,name', 'tags']);

        // Load post likes data for the current user
        $post->loadCount('likes');
        if (auth()->check()) {
            $post->load(['likes' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }

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
                    ->map(static fn (Comment $child): array => $mapComment($child))
                    ->values(),
            ];
        };

        $rootComments = collect($byParent->get(null))
            ->map(static fn (Comment $c): array => $mapComment($c))
            ->values();

        $title = $post->title ?? Str::headline($post->slug);

        return Inertia::render('Posts/Show', [
            'post' => $post->only(['id', 'title', 'slug', 'content', 'body', 'excerpt', 'type', 'views_count', 'created_at']) + [
                'author' => $post->user?->only(['id', 'name']),
                'comments' => $rootComments,
                'tags' => $post->tags->map(fn ($tag) => $tag->only(['name', 'color']))->toArray(),
                'likes_count' => $post->likes_count,
                'is_liked' => auth()->check() && $post->likes->isNotEmpty(),
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

        // Associate category using Eloquent relationship
        if (! empty($data['category_id'])) {
            $post->category()->associate($data['category_id']);
            $post->save();
        }

        // Handle tags - create if they don't exist, then attach
        $tagNames = $data['tags'] ?? [];
        if (! empty($tagNames)) {
            $tagIds = $this->createOrFindTags($tagNames);
            $post->tags()->attach($tagIds);
        }

        return redirect()->route('posts.show', $post);
    }

    public function create(): Response
    {
        $this->authorize('create', Post::class);

        return Inertia::render('Posts/Create', [
            'postTypes' => collect(PostType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ])->sortBy('label')->values(),
            'categories' => Category::select(['id', 'name', 'slug', 'color'])
                ->orderBy('name')
                ->get()
                ->map(fn ($category) => [
                    'value' => $category->id,
                    'label' => $category->name,
                    'slug' => $category->slug,
                    'color' => $category->color,
                ]),
        ]);
    }

    /**
     * Create or find tags by name and return their IDs.
     *
     * @param  array<string>  $tagNames
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
                    'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)), // Random color
                    'description' => '', // Empty description for auto-created tags
                ]
            );

            $tagIds[] = $tag->id;
        }

        return $tagIds;
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
            'postTypes' => collect(PostType::cases())->map(fn ($type) => [
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
        if (! empty($tagNames)) {
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

        if (! $existingLike) {
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
