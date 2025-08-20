<?php

namespace Tests\Unit;

use App\Models\Attachment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_mass_assignment_allows_expected_fields(): void
    {
        $user = User::factory()->create();

        $post = $user->posts()->create([
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'content' => 'Post content',
            'excerpt' => 'Post excerpt',
            'type' => 'article',
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Hello World',
            'slug' => 'hello-world',
            'user_id' => $user->id,
        ]);
    }

    public function test_post_mass_assignment_disallows_user_id(): void
    {
        $user = User::factory()->create();

        $post = $user->posts()->create([
            'title' => 'Should not override user',
            'slug' => 'should-not-override-user',
            'content' => 'x',
            'excerpt' => 'x',
            'type' => 'article',
            'user_id' => 999, // should be ignored by mass assignment
        ]);

        $this->assertSame($user->id, $post->user_id, 'user_id should come from relationship, not mass-assigned value');
    }

    public function test_comment_mass_assignment_allows_content_and_parent_id(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Post A',
            'slug' => 'post-a',
            'content' => 'C',
            'excerpt' => 'E',
            'type' => 'article',
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
    }

    public function test_comment_mass_assignment_disallows_user_and_post_ids(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Post C',
            'slug' => 'post-c',
            'content' => 'C',
            'excerpt' => 'E',
            'type' => 'article',
        ]);

        $comment = $post->comments()->make([
            'content' => 'Ignore these ids',
            'post_id' => 999,
            'user_id' => 999,
        ]);
        $comment->user()->associate($user);
        $comment->save();

        $comment->refresh();
        $this->assertSame($post->id, $comment->post_id, 'post_id should come from relationship, not mass-assigned value');
        $this->assertSame($user->id, $comment->user_id, 'user_id should come from association, not mass-assigned value');
    }

    public function test_attachment_mass_assignment_only_allows_type(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Post B',
            'slug' => 'post-b',
            'content' => 'C',
            'excerpt' => 'E',
            'type' => 'article',
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
    }

    public function test_attachment_mass_assignment_disallows_foreign_keys_and_counters(): void
    {
        $user = User::factory()->create();
        $post = $user->posts()->create([
            'title' => 'Post D',
            'slug' => 'post-d',
            'content' => 'C',
            'excerpt' => 'E',
            'type' => 'article',
        ]);

        $attachment = $post->attachments()->make([
            'type' => 'doc',
            'download_count' => 99, // should be ignored
            'user_id' => 999, // should be ignored
        ]);

        // Because download_count is not fillable, it should not be set from mass assignment
        $this->assertNull($attachment->download_count);

        $attachment->user()->associate($user);
        // Set required attributes programmatically
        $attachment->file_size = 1;
        $attachment->mime_type = 'application/octet-stream';
        $attachment->download_count = 0;
        $attachment->save();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'doc',
            'download_count' => 0,
        ]);
    }

    public function test_like_is_not_mass_assignable(): void
    {
        $this->expectException(MassAssignmentException::class);

        Like::create([
            'user_id' => 1,
            'likeable_type' => Post::class,
            'likeable_id' => 1,
        ]);
    }
}
