<?php

namespace App\Models;

use App\Enum\PostType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'content',
        'body',
        'excerpt',
        'type',
        'views_count',
    ];

    protected $appends = ['created_at_human'];

    /**
     * Automatically generate a unique slug from the title when creating/updating.
     */
    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            if ($post->title !== null) {
                $post->title = Str::title($post->title);
                $post->slug = $post->generateUniqueSlug($post->title);
            }

            // Only auto-generate excerpt if user didn't provide one
            if ($post->content !== null && empty($post->excerpt)) {
                $post->excerpt = $post->generateExcerpt($post->content);
            }
        });

        static::updating(function (Post $post): void {
            if ($post->isDirty('title') && $post->title !== null) {
                $post->title = Str::title($post->title);
                $post->slug = $post->generateUniqueSlug($post->title, $post->id);
            }

            // Only auto-generate excerpt if user didn't provide one and content changed
            if ($post->isDirty('content') && $post->content !== null && empty($post->excerpt)) {
                $post->excerpt = $post->generateExcerpt($post->content);
            }
        });
    }

    /**
     * Build a URL friendly unique slug for the given title.
     */
    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;

        while (static::query()
            ->when($ignoreId !== null, fn($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Generate an excerpt from the content.
     */
    public function generateExcerpt(string $content): string
    {
        // Strip HTML tags and markdown formatting
        $cleanContent = strip_tags($content);

        // Remove multiple whitespace and normalize line breaks
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);

        // Trim whitespace
        $cleanContent = trim($cleanContent);

        // Generate excerpt with custom length (default is 100 words)
        $excerpt = Str::words($cleanContent, 25, '...');

        // Ensure excerpt doesn't exceed database column limit (255 characters)
        return Str::limit($excerpt, 252, '...');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getCreatedAtHumanAttribute(): string
    {
        return $this->created_at?->diffForHumans([
            'short' => true,
        ]) ?? '';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag', 'post_id', 'tag_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Scope a query to order posts by view count (most viewed first)
     */
    #[Scope]
    protected function mostViewed(Builder $query): void
    {
        $query->orderBy('views_count', 'desc');
    }

    /**
     * Scope a query to limit results to top N posts
     */
    #[Scope]
    protected function topViewed(Builder $query, int $limit = 10): void
    {
        $query->take($limit);
    }

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
        ];
    }
}
