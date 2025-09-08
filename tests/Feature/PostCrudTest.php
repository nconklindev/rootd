<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guest cannot view index or show', function () {
    $user = User::factory()->create();
    $post = $user->posts()->create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => 'Post content',
        'excerpt' => 'Excerpt',
        'type' => 'text',
    ]);

    $this->get(route('posts.index'))->assertRedirect('/login');
    $this->get(route('posts.show', $post))->assertRedirect('/login');
});

test('guest cannot access create or store', function () {
    $this->get(route('posts.create'))->assertRedirectToRoute('login');
    $this->post(route('posts.store'), [])->assertRedirectToRoute('login');
});

test('user can create post', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'content' => 'Body',
        'excerpt' => 'Short',
        'type' => 'text',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('posts', [
        'slug' => 'my-first-post',
        'user_id' => $user->id,
    ]);
});

test('only owner can update post', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $post = $owner->posts()->create([
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'content' => 'A',
        'excerpt' => 'E',
        'type' => 'text',
    ]);

    $this->actingAs($other)
        ->put(route('posts.update', $post), [
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'content' => 'B',
            'excerpt' => 'E',
            'type' => 'text',
        ])->assertForbidden();

    $this->actingAs($owner)
        ->put(route('posts.update', $post), [
            'title' => 'My First Post',
            'slug' => 'my-first-post',
            'content' => 'B',
            'excerpt' => 'E',
            'type' => 'text',
        ])->assertRedirect();

    $this->assertDatabaseHas('posts', [
        'slug' => 'my-first-post',
        'content' => 'B',
    ]);
});

test('only owner can delete post', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $post = $owner->posts()->create([
        'title' => 'My First Post',
        'slug' => 'my-first-post',
        'content' => 'Post content',
        'excerpt' => 'Excerpt',
        'type' => 'text',
    ]);

    // Other user cannot delete
    $this->actingAs($other)
        ->delete(route('posts.destroy', $post))
        ->assertForbidden();

    // Owner can delete
    $this->actingAs($owner)
        ->delete(route('posts.destroy', $post))
        ->assertRedirect(route('posts.index'));

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
    ]);
});
