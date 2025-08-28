<?php

use App\Models\Post;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('slug is generated from title on create', function () {
    $user = User::factory()->create();

    $post = $user->posts()->create([
        'title' => 'My First Post',
        'content' => 'Body',
        'excerpt' => 'Short',
        'type' => 'text',
        'status' => 'draft',
    ]);

    expect($post->slug)->toBe('my-first-post');
});

test('slug updates when title changes and is unique', function () {
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

    expect($post1->slug)->toBe('duplicate-title');
    expect($post2->slug)->toBe('duplicate-title-2');
});