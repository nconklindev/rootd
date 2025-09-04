<?php

namespace App\Actions\Posts;

use App\Models\Tag;
use Illuminate\Support\Str;
use Log;

class CreateOrFindTagsAction
{
    /**
     * Create or find tags by name and return their IDs.
     * Includes duplicate prevention through similarity checking.
     *
     * @param array<string> $tagNames
     * @return array<int>
     */
    public function handle(array $tagNames): array
    {
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            // Clean up the tag name
            $cleanName = trim($tagName);
            if (empty($cleanName)) {
                continue;
            }

            // First, check if there are similar existing tags
            $similarTags = Tag::findSimilarTags($cleanName, 0.85);

            if ($similarTags->isNotEmpty()) {
                // Use the most similar existing tag instead of creating a new one
                $existingTag = $similarTags->first();
                $tagIds[] = $existingTag->id;

                // Optionally log this for admin review
                Log::info("Tag suggestion used: '{$cleanName}' -> '{$existingTag->name}'");
            } else {
                // No similar tags found, create new one
                $tag = Tag::firstOrCreate(
                    ['name' => $cleanName],
                    [
                        'name' => $cleanName,
                        'slug' => Str::slug($cleanName),
                        'color' => $this->generateTagColor(),
                    ]
                );

                $tagIds[] = $tag->id;
            }
        }

        return array_unique($tagIds); // Remove any potential duplicates
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
            '#4338CA', // Indigo-700
            '#15803D', // Green-700
            '#B91C1C', // Red-700
            '#86198F', // Fuchsia-700
            '#1D4ED8', // Blue-700
            '#0F766E', // Teal-700
            '#7E22CE', // Purple-700
            '#B45309', // Amber-700
            '#0E7490', // Cyan-700
            '#4D7C0F', // Lime-700
        ];

        return $colors[array_rand($colors)];
    }
}
