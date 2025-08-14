<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTypeValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_post_type_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'slug' => 'invalid-type',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'weird',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_valid_enum_post_type_is_accepted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'slug' => 'uses-video',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'video',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('posts', [
            'slug' => 'uses-video',
            'type' => 'video',
        ]);
    }
}
