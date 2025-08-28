<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guest cannot post comment', function () {
    $post = Post::factory()->create();

    $response = $this->post(route('posts.comments.store', $post), [
        'content' => 'Hello world',
    ]);

    $response->assertRedirectToRoute('login');
});

test('authenticated user can post comment', function () {
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
});

test('authenticated user can like comment', function () {
    $user = User::factory()->create();
    $comment = Comment::factory()->create();

    $response = $this->actingAs($user)->post(route('comments.like', $comment));

    $response->assertRedirect();

    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'likeable_type' => Comment::class,
        'likeable_id' => $comment->id,
    ]);
});

test('authenticated user can unlike comment', function () {
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
});

test('guest cannot like comment', function () {
    $comment = Comment::factory()->create();

    $response = $this->post(route('comments.like', $comment));

    $response->assertRedirectToRoute('login');
});

test('guest cannot unlike comment', function () {
    $comment = Comment::factory()->create();

    $response = $this->delete(route('comments.unlike', $comment));

    $response->assertRedirectToRoute('login');
});