<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
        'usage_count',
        'last_used_at',
    ];

    /**
     * Suggest existing tags for a given input
     */
    public static function suggestTags(string $input, int $limit = 5): Collection
    {
        // First, find similar tags
        $similarTags = self::findSimilarTags($input, 0.6);

        // If no similar tags, find tags that contain the input or vice versa
        if ($similarTags->isEmpty()) {
            $containmentTags = self::where('name', 'ILIKE', '%'.$input.'%')
                ->orWhere(DB::raw('LOWER(name)'), 'ILIKE', '%'.strtolower($input).'%')
                ->orderBy('usage_count', 'desc')
                ->limit($limit)
                ->get();

            return $containmentTags;
        }

        return $similarTags->take($limit);
    }

    /**
     * Find similar tags that might be duplicates
     */
    public static function findSimilarTags(string $tagName, float $threshold = 0.8): Collection
    {
        // Return empty collection for empty or very short inputs
        if (strlen(trim($tagName)) < 2) {
            return collect();
        }

        $normalizedInput = self::normalizeTagName($tagName);

        return self::all()->filter(function (Tag $tag) use ($normalizedInput, $threshold) {
            $normalizedExisting = self::normalizeTagName($tag->name);
            $similarity = self::calculateSimilarity($normalizedInput, $normalizedExisting);

            return $similarity >= $threshold;
        })->sortByDesc(function (Tag $tag) use ($normalizedInput) {
            return self::calculateSimilarity($normalizedInput, self::normalizeTagName($tag->name));
        });
    }

    /**
     * Normalize tag name for comparison
     */
    public static function normalizeTagName(string $name): string
    {
        // Convert to lowercase
        $normalized = strtolower($name);

        // Remove common suffixes/prefixes
        $patterns = [
            '/\s+(framework|library|js|php|lang|language)$/i',
            '/^(the|a|an)\s+/i',
        ];

        foreach ($patterns as $pattern) {
            $normalized = preg_replace($pattern, '', $normalized);
        }

        // Remove extra spaces and trim
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        return $normalized;
    }

    /**
     * Calculate similarity between two strings using multiple algorithms
     */
    public static function calculateSimilarity(string $str1, string $str2): float
    {
        // Exact match
        if ($str1 === $str2) {
            return 1.0;
        }

        // Levenshtein distance (for typos)
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen > 0) {
            $levenshtein = 1 - (levenshtein($str1, $str2) / $maxLen);
        } else {
            $levenshtein = 0;
        }

        // Similar text (for word order variations)
        similar_text($str1, $str2, $similarText);
        $similarText /= 100;

        // Check if one is contained in the other (for "Laravel" vs "Laravel Framework")
        $containment = 0;
        if (str_contains($str1, $str2) || str_contains($str2, $str1)) {
            $containment = min(strlen($str1), strlen($str2)) / max(strlen($str1), strlen($str2));
        }

        // Special handling for abbreviations (e.g., "JS" vs "JavaScript")
        $abbreviation = 0;
        if (strlen($str1) <= 3 || strlen($str2) <= 3) {
            $shorter = strlen($str1) < strlen($str2) ? $str1 : $str2;
            $longer = strlen($str1) >= strlen($str2) ? $str1 : $str2;

            // Check if shorter string matches the first letters of the longer string
            if (str_starts_with($longer, $shorter)) {
                $abbreviation = 0.8; // High score for potential abbreviations
            } else {
                // Check for common abbreviation patterns
                $abbreviationMap = [
                    'js' => 'javascript',
                    'php' => 'php', // This will match exactly anyway
                    'css' => 'cascading style sheets',
                    'html' => 'hypertext markup language',
                    'sql' => 'structured query language',
                ];

                $shorterLower = strtolower($shorter);
                $longerLower = strtolower($longer);

                if (isset($abbreviationMap[$shorterLower]) && str_contains($longerLower, $abbreviationMap[$shorterLower])) {
                    $abbreviation = 0.85; // High score for known abbreviations
                } elseif (isset($abbreviationMap[$longerLower]) && str_contains($shorterLower, $abbreviationMap[$longerLower])) {
                    $abbreviation = 0.85;
                }
            }
        }

        // Return the highest similarity score
        return max($levenshtein, $similarText, $containment, $abbreviation);
    }

    protected static function booted(): void
    {
        static::creating(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tag', 'tag_id', 'post_id');
    }

    /**
     * Merge this tag with another tag (administrative function)
     */
    public function mergeWith(Tag $targetTag): bool
    {
        if ($this->id === $targetTag->id) {
            return false;
        }

        DB::transaction(function () use ($targetTag) {
            // Move all post relationships to the target tag
            DB::table('post_tag')
                ->where('tag_id', $this->id)
                ->update(['tag_id' => $targetTag->id]);

            // Update target tag usage count
            $targetTag->increment('usage_count', $this->usage_count ?? 0);
            $targetTag->update(['last_used_at' => now()]);

            // Delete this tag
            $this->delete();
        });

        return true;
    }
}
