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
            'slug' => 'hello-world',
            'content' => 'Post content',
            'excerpt' => 'Excerpt',
            'type' => 'article',
            'status' => 'published',
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
            'slug' => 'my-post',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'article',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'slug' => 'my-post',
            'user_id' => $user->id,
        ]);
    }

    public function test_only_owner_can_update_post(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $post = $owner->posts()->create([
            'slug' => 'edit-me',
            'content' => 'A',
            'excerpt' => 'E',
            'type' => 'article',
            'status' => 'draft',
        ]);

        $this->actingAs($other)
            ->put(route('posts.update', $post), [
                'slug' => 'edit-me',
                'content' => 'B',
                'excerpt' => 'E',
                'type' => 'article',
                'status' => 'draft',
            ])->assertForbidden();

        $this->actingAs($owner)
            ->put(route('posts.update', $post), [
                'slug' => 'edit-me',
                'content' => 'B',
                'excerpt' => 'E',
                'type' => 'article',
                'status' => 'published',
            ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'slug' => 'edit-me',
            'content' => 'B',
            'status' => 'published',
        ]);
    }
}
