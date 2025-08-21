<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'color',
        'slug',
    ];

    /**
     * Automatically generate a unique slug from the name when creating/updating.
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            if ($category->name !== null) {
                $category->slug = $category->generateUniqueSlug($category->name);
            }
        });

        static::updating(function (Category $category): void {
            if ($category->isDirty('name') && $category->name !== null) {
                $category->slug = $category->generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    /**
     * Build a URL friendly unique slug for the given name.
     */
    public function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
