<?php

use App\Enum\PostType;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('invalid post type is rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('posts.store'), [
        'title' => 'Test Post',
        'slug' => 'invalid-type',
        'content' => 'Body',
        'excerpt' => 'Short',
        'type' => 'weird',
    ]);

    $response->assertSessionHasErrors(['type']);
});

test('valid post types are accepted', function () {
    $user = User::factory()->create();

    foreach (PostType::cases() as $postType) {
        $response = $this->actingAs($user)->post(route('posts.store'), [
            'title' => 'Test Post '.$postType->value,
            'slug' => 'test-post-'.$postType->value,
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => $postType->value,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('posts', [
            'slug' => 'test-post-'.$postType->value,
            'type' => $postType->value,
        ]);
    }
});
