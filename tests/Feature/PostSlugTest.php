<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_from_title_on_create(): void
    {
        $user = User::factory()->create();

        $post = $user->posts()->create([
            'title' => 'My First Post',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'text',
            'status' => 'draft',
        ]);

        $this->assertSame('my-first-post', $post->slug);
    }

    public function test_slug_updates_when_title_changes_and_is_unique(): void
    {
        $user = User::factory()->create();

        $post1 = $user->posts()->create([
            'title' => 'Duplicate Title',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'text',
            'status' => 'draft',
        ]);

        $post2 = $user->posts()->create([
            'title' => 'Another Title',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'text',
            'status' => 'draft',
        ]);

        // Update second post to the same title
        $post2->update(['title' => 'Duplicate Title']);

        $this->assertSame('duplicate-title', $post1->slug);
        $this->assertSame('duplicate-title-2', $post2->slug);
    }
}
