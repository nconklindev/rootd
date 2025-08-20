<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_like_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $post->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // First like the post
        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);
        
        // Then unlike it
        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->delete(route('posts.unlike', $post->id), ['_token' => 'test-token']);

        $response->assertRedirect();
        
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => Post::class,
            'likeable_id' => $post->id,
        ]);
    }

    public function test_user_cannot_like_same_post_twice(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Like the post twice
        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);
        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

        // Should only have one like record
        $this->assertEquals(1, $post->likes()->count());
    }

    public function test_post_show_includes_like_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // User likes the post
        $this->actingAs($user)
            ->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

        // Visit the post page
        $response = $this->actingAs($user)->get(route('posts.show', $post->slug));

        $response->assertStatus(200);
        $response->assertInertia(fn($assert) => $assert
            ->component('Posts/Show')
            ->where('post.is_liked', true)
            ->where('post.likes_count', 1)
        );
    }

    public function test_guest_cannot_like_posts(): void
    {
        $post = Post::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

        $response->assertRedirect(route('login'));
    }
}