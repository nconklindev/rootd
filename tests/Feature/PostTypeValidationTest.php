<?php

namespace Tests\Feature;

use App\Enum\PostType;
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
            'title' => 'Test Post',
            'slug' => 'invalid-type',
            'content' => 'Body',
            'excerpt' => 'Short',
            'type' => 'weird',
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_valid_post_types_are_accepted(): void
    {
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
    }
}
