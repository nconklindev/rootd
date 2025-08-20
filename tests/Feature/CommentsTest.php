<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_post_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->post(route('posts.comments.store', $post), [
            'content' => 'Hello world',
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_can_post_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.comments.store', $post), [
            'content' => 'This is a test comment',
        ]);

        $response->assertRedirect(route('posts.show', $post));

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'This is a test comment',
        ]);
    }

    public function test_authenticated_user_can_like_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)->post(route('comments.like', $comment));

        $response->assertRedirect();

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $comment->id,
        ]);
    }

    public function test_authenticated_user_can_unlike_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        // First like the comment
        $like = new \App\Models\Like;
        $like->user_id = $user->id;
        $comment->likes()->save($like);

        $response = $this->actingAs($user)->delete(route('comments.unlike', $comment));

        $response->assertRedirect();

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => Comment::class,
            'likeable_id' => $comment->id,
        ]);
    }

    public function test_guest_cannot_like_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->post(route('comments.like', $comment));

        $response->assertRedirectToRoute('login');
    }

    public function test_guest_cannot_unlike_comment(): void
    {
        $comment = Comment::factory()->create();

        $response = $this->delete(route('comments.unlike', $comment));

        $response->assertRedirectToRoute('login');
    }
}
