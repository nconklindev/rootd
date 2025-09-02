<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Create a small pool of users to associate content with
        $users = User::factory()->count(5)->create();

        // Create a diverse pool of tags for posts
        $tagNames = [
            'Laravel', 'PHP', 'JavaScript', 'Vue.js', 'React', 'Node.js',
            'Database', 'MySQL', 'PostgreSQL', 'Redis', 'Docker',
            'API', 'REST', 'GraphQL', 'Testing', 'Security',
            'Performance', 'Frontend', 'Backend', 'DevOps',
            'Tutorial', 'Tips', 'Best Practices', 'Code Review',
            'Architecture', 'Design Patterns', 'Algorithms',
        ];

        $tags = collect($tagNames)->map(function ($tagName) {
            return Tag::firstOrCreate(
                ['name' => $tagName],
                [
                    'name' => $tagName,
                    'color' => $this->generateTagColor(),
                ]
            );
        });

        // Create posts per user
        $users->each(function (User $user) use ($users, $tags): void {
            $posts = Post::factory()
                ->count(fake()->numberBetween(2, 5))
                ->for($user)
                ->create();

            $posts->each(function (Post $post) use ($user, $users, $tags): void {

                // Attachments
                Attachment::factory()
                    ->count(fake()->numberBetween(0, 3))
                    ->for($post)
                    ->for($user)
                    ->create();

                // Top-level comments
                $topLevelComments = Comment::factory()
                    ->count(fake()->numberBetween(0, 4))
                    ->for($post)
                    ->for($users->random())
                    ->create();

                // Replies to top-level comments
                $topLevelComments->each(function (Comment $parent) use ($users): void {
                    Comment::factory()
                        ->count(fake()->numberBetween(0, 3))
                        ->state(fn () => [
                            'post_id' => $parent->post_id,
                            'parent_id' => $parent->id,
                            'user_id' => $users->random()->id,
                        ])
                        ->create();
                });

                // Likes on the post (ensure no duplicate likers and avoid mass assignment)
                $likeCount = fake()->numberBetween(0, min(5, $users->count()));
                if ($likeCount > 0) {
                    $likers = $users->shuffle()->take($likeCount);

                    $likers->each(function (User $liker) use ($post): void {
                        $like = new Like;
                        $like->user()->associate($liker);
                        $post->likes()->save($like);
                    });
                }

                // Attach 1-5 random tags to each post
                $tagCount = fake()->numberBetween(1, 5);
                $postTags = $tags->shuffle()->take($tagCount);
                $post->tags()->attach($postTags->pluck('id')->toArray());
            });
        });

        // Ensure there are enough posts to test pagination clearly
        $minPosts = 100; // with per-page=10, yields at least 5 pages
        $postCount = Post::query()->count();
        if ($postCount < $minPosts) {
            $additionalPosts = Post::factory()->count($minPosts - $postCount)->create();

            // Add tags to the additional posts too
            $additionalPosts->each(function (Post $post) use ($tags): void {
                $tagCount = fake()->numberBetween(1, 5);
                $postTags = $tags->shuffle()->take($tagCount);
                $post->tags()->attach($postTags->pluck('id')->toArray());
            });
        }
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
}
