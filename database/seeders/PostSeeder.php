<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        // Create a small pool of users to associate content with
        $users = User::factory()->count(5)->create();

        // Create posts per user
        $users->each(function (User $user) use ($users): void {
            $posts = Post::factory()
                ->count(fake()->numberBetween(2, 5))
                ->for($user)
                ->create();

            $posts->each(function (Post $post) use ($user, $users): void {

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
            });
        });

        // Ensure there are enough posts to test pagination clearly
        $minPosts = 100; // with per-page=10, yields at least 5 pages
        $postCount = Post::query()->count();
        if ($postCount < $minPosts) {
            Post::factory()->count($minPosts - $postCount)->create();
        }
    }
}
