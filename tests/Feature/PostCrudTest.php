<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_index_and_show_published(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => 'Post content',
            'excerpt' => 'Excerpt',
            'type' => 'article',
        ]);

        $this->get(route('posts.index'))->assertOk();
        $this->get(route('posts.show', $post))->assertOk();
    }

    public function test_guest_cannot_access_create_or_store(): void
    {
        $this->get(route('posts.create'))->assertRedirectToRoute('login');
        $this->post(route('posts.store'), [])->assertRedirectToRoute('login');
    }

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'article',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'slug' => 'my-first-post',
            'user_id' => $user->id,
        ]);
    }

    public function test_only_owner_can_update_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $post = $owner->posts()->create([
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'content' => 'A',
            'excerpt' => 'E',
            'type' => 'article',
        ]);

        $this->actingAs($other)
            ->put(route('posts.update', $post), [
                'title' => 'My First Post',
                'slug' => 'my-first-post',
                'content' => 'B',
                'excerpt' => 'E',
                'type' => 'article',
            ])->assertForbidden();

        $this->actingAs($owner)
            ->put(route('posts.update', $post), [
                'title' => 'My First Post',
                'slug' => 'my-first-post',
                'content' => 'B',
                'excerpt' => 'E',
                'type' => 'article',
            ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'slug' => 'my-first-post',
            'content' => 'B',
        ]);
    }
}
