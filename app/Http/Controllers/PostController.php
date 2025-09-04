<?php

namespace App\Http\Controllers;

use App\Actions\Posts\BuildCommentTreeAction;
use App\Actions\Posts\CreateOrFindTagsAction;
use App\Actions\Posts\TransformPostAction;
use App\Enum\PostType;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Services\PostViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
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

    public function index(Request $request, TransformPostAction $transformPostAction): Response
    {
        $posts = Post::query()
            ->latest('id')
            ->select(['id', 'title', 'slug', 'excerpt', 'type', 'user_id', 'category_id', 'created_at', 'views_count'])
            ->with(['user:id,name,username', 'category:id,name,slug,color', 'tags:id,name,slug,color', 'attachments:id,post_id,original_filename,mime_type'])
            ->withCount('comments', 'likes', 'attachments')
            ->when(auth()->check(), function ($query) {
                $query->with(['likes' => function ($q) {
                    $q->where('user_id', auth()->id());
                }]);
            })
            ->paginate(10)
            ->withQueryString();

        $transformPostAction->handle($posts);

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

    public function show(Post $post, BuildCommentTreeAction $action): Response
    {
        // Authorize viewing
        $this->authorize('view', $post);

        // Record the view (this will handle duplicate prevention)
        $this->postViewService->recordView($post);

        $post->load(['user:id,name,username', 'tags', 'attachments']);

        // Load post likes data for the current user
        $post->loadCount('likes');
        if (auth()->check()) {
            $post->load(['likes' => function ($q) {
                $q->where('user_id', auth()->id());
            }]);
        }

        // Build comment tree using the action
        $comments = $action->handle($post);

        $title = $post->title ?? Str::headline($post->slug);

        return Inertia::render('Posts/Show', [
            'post' => $post->only(['id', 'title', 'slug', 'content', 'body', 'excerpt', 'type', 'views_count', 'created_at']) + [
                    'author' => $post->user?->only(['id', 'name', 'username']),
                    'comments' => $comments,
                    'tags' => $post->tags->map(fn($tag) => $tag->only(['name', 'color']))->toArray(),
                    'attachments' => $post->attachments->map(fn($attachment) => [
                        'id' => $attachment->id,
                        'original_filename' => $attachment->original_filename,
                        'file_path' => $attachment->file_path,
                        'file_size' => Number::fileSize($attachment->file_size),
                        'mime_type' => $attachment->mime_type,
                        'download_count' => $attachment->download_count,
                        'url' => asset('storage/' . $attachment->file_path),
                    ])->toArray(),
                    'likes_count' => $post->likes_count,
                    'is_liked' => auth()->check() && $post->likes->isNotEmpty(),
                ],
            'title' => $title,
        ]);
    }

    public function store(StorePostRequest $request, CreateOrFindTagsAction $action): RedirectResponse
    {
        $this->authorize('create', Post::class);

        // Create a new Post instance and fill it with validated data
        $post = new Post;
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
            $tagIds = $action->handle($tagNames);
            $post->tags()->attach($tagIds);
        }

        // Handle file upload
        // Start by checking if the file upload is valid
        // This may be redundant because we're already validating, but it probably doesn't hurt
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $uploadedFile = $request->file('file');

            // Store file with unique name in the 'attachments' directory
            $path = $uploadedFile->store('attachments', 'public');

            // Create attachment record with all data including user_id
            $attachment = $post->attachments()->create([
                'user_id' => $request->user()->id,
                'file_path' => $path,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'file_size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'download_count' => 0,
            ]);
        }

        return to_route('posts.show', $post);
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

    public function update(UpdatePostRequest $request, Post $post, CreateOrFindTagsAction $action): RedirectResponse
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
            $tagIds = $action->handle($tagNames);
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

    public function myPosts(TransformPostAction $transformPostAction): Response
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
        $transformPostAction->handle($posts);

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
