<?php

use App\Models\Post;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('authenticated user can like a post', function () {
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
});

test('authenticated user can unlike a post', function () {
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
});

test('user cannot like same post twice', function () {
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
    expect($post->likes()->count())->toEqual(1);
});

test('post show includes like data', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create();

    // User likes the post
    $this->actingAs($user)
        ->withSession(['_token' => 'test-token'])
        ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

    // Visit the post page
    $response = $this->actingAs($user)->get(route('posts.show', $post->slug));

    $response->assertStatus(200);
    $response->assertInertia(fn ($assert) => $assert
        ->component('Posts/Show')
        ->where('post.is_liked', true)
        ->where('post.likes_count', 1)
    );
});

test('guest cannot like posts', function () {
    $post = Post::factory()->create();

    $response = $this->withSession(['_token' => 'test-token'])
        ->post(route('posts.like', $post->id), ['_token' => 'test-token']);

    $response->assertRedirect(route('login'));
});
