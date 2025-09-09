<?php

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses(Tests\TestCase::class);

test('post mass assignment allows expected fields', function () {
    $user = User::factory()->create();

    $post = $user->posts()->create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'content' => 'Post content',
        'excerpt' => 'Post excerpt',
        'type' => 'text',
    ]);

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'user_id' => $user->id,
    ]);
});

test('post mass assignment disallows user id', function () {
    $user = User::factory()->create();

    $post = $user->posts()->create([
        'title' => 'Should not override user',
        'slug' => 'should-not-override-user',
        'content' => 'x',
        'excerpt' => 'x',
        'type' => 'text',
        'user_id' => 999, // should be ignored by mass assignment
    ]);

    expect($post->user_id)->toBe($user->id, 'user_id should come from relationship, not mass-assigned value');
});

test('comment mass assignment allows content and parent id', function () {
    $user = User::factory()->create();
    $post = $user->posts()->create([
        'title' => 'Post A',
        'slug' => 'post-a',
        'content' => 'C',
        'excerpt' => 'E',
        'type' => 'text',
    ]);

    $comment = $post->comments()->make([
        'content' => 'Nice post',
        'parent_id' => null,
    ]);
    $comment->user()->associate($user);
    $comment->save();

    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'post_id' => $post->id,
        'user_id' => $user->id,
        'content' => 'Nice post',
    ]);
});

test('comment mass assignment cannot set user_id or post_id; relationships must', function () {
    Model::preventSilentlyDiscardingAttributes();

    $user = User::factory()->create();
    $post = Post::factory()->create();

    // Prove mass assigning IDs is not allowed
    // This should throw an exception causing the test to pass since we are expecting it
    expect(fn () => Comment::create([
        'content' => 'Nice post',
        'post_id' => $post->id,
        'user_id' => $user->id,
    ]))->toThrow(MassAssignmentException::class);
});

test('attachment mass assignment only allows type', function () {
    $user = User::factory()->create();
    $post = $user->posts()->create([
        'title' => 'Post B',
        'slug' => 'post-b',
        'content' => 'C',
        'excerpt' => 'E',
        'type' => 'text',
    ]);

    $attachment = new Attachment(['type' => 'image']);
    $attachment->post()->associate($post);
    $attachment->user()->associate($user);
    $attachment->file_size = 1024;
    $attachment->mime_type = 'image/png';
    $attachment->download_count = 0;
    $attachment->save();

    $this->assertDatabaseHas('attachments', [
        'id' => $attachment->id,
        'post_id' => $post->id,
        'user_id' => $user->id,
        'type' => 'image',
        'file_size' => 1024,
        'mime_type' => 'image/png',
        'download_count' => 0,
    ]);
});

test('attachment mass assignment disallows does not allow assigning user_id', function () {
    Model::preventSilentlyDiscardingAttributes();

    $user = User::factory()->create();
    $post = Post::factory()->create();

    expect(fn () => Attachment::create([
        'type' => 'doc',
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
        'user_id' => $user->id,
        'post_id' => $post->id,
        'download_count' => 0,
    ]))->toThrow(MassAssignmentException::class);
});

test('like is not mass assignable', function () {
    Model::preventSilentlyDiscardingAttributes();

    expect(fn () => Like::create([
        'user_id' => 1,
        'likeable_type' => Post::class,
        'likeable_id' => 1,
    ]))->toThrow(MassAssignmentException::class);
});
