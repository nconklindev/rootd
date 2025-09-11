<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tags are successfully created when used in a post for the first time', function () {
    // Create a user and category for the post
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Ensure the tags don't exist yet
    expect(Tag::where('name', 'Laravel')->exists())->toBeFalse()
        ->and(Tag::where('name', 'Testing')->exists())->toBeFalse()
        ->and(Tag::count())->toBe(0);

    // Create a post with new tags
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'My First Laravel Post',
        'content' => 'This is a comprehensive guide to Laravel testing.',
        'excerpt' => 'Learn Laravel testing basics',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['Laravel', 'Testing', 'PHP'],
    ]);

    // Assert the response was successful
    $response->assertStatus(302);

    // Assert tags were created
    expect(Tag::count())->toBe(3)
        ->and(Tag::where('name', 'Laravel')->exists())->toBeTrue()
        ->and(Tag::where('name', 'Testing')->exists())->toBeTrue()
        ->and(Tag::where('name', 'PHP')->exists())->toBeTrue();

    // Verify tag properties
    $laravelTag = Tag::where('name', 'Laravel')->first();
    expect($laravelTag->slug)->toBe('laravel')
        ->and($laravelTag->color)->not->toBeEmpty();

    // Verify the post was created and tags are attached
    $post = Post::where('title', 'My First Laravel Post')->first();
    expect($post)->not->toBeNull()
        ->and($post->tags)->toHaveCount(3)
        ->and($post->tags->pluck('name')->toArray())->toContain('Laravel', 'Testing', 'PHP');
});

test('existing tags are reused when creating posts', function () {
    // Create a user and category
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create existing tags
    $existingTag = Tag::create([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'color' => '#FF0000',
    ]);

    expect(Tag::count())->toBe(1);

    // Create a post with mixed existing and new tags
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Advanced Laravel Topics',
        'content' => 'Deep dive into Laravel features.',
        'excerpt' => 'Advanced Laravel concepts',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['Laravel', 'Advanced', 'Framework'], // Laravel exists, others are new
    ]);

    // Assert response was successful
    $response->assertStatus(302);

    // Assert only 2 new tags were created (Laravel already existed)
    expect(Tag::count())->toBe(3)
        ->and(Tag::where('name', 'Advanced')->exists())->toBeTrue()
        ->and(Tag::where('name', 'Framework')->exists())->toBeTrue();

    // Verify the existing Laravel tag wasn't modified
    $laravelTag = Tag::where('name', 'Laravel')->first();
    expect($laravelTag->id)->toBe($existingTag->id)
        ->and($laravelTag->color)->toBe('#FF0000');

    // Verify the post has all three tags
    $post = Post::where('title', 'Advanced Laravel Topics')->first();
    expect($post->tags)->toHaveCount(3)
        ->and($post->tags->pluck('name')->toArray())->toContain('Laravel', 'Advanced', 'Framework');
});

test('empty and whitespace-only tag names are filtered out', function () {
    // Create a user and category
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create a post with empty/whitespace tags mixed with valid ones
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Post with Mixed Tags',
        'content' => 'Content with various tag formats.',
        'excerpt' => 'Testing tag filtering',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['Valid Tag', '', '   ', 'Another Valid Tag'],
    ]);

    // Assert response was successful
    $response->assertStatus(302);

    // Assert only valid tags were created
    expect(Tag::count())->toBe(2)
        ->and(Tag::where('name', 'Valid Tag')->exists())->toBeTrue()
        ->and(Tag::where('name', 'Another Valid Tag')->exists())->toBeTrue();

    // Verify the post has only valid tags (note: title gets title-cased automatically)
    $post = Post::where('title', 'Post With Mixed Tags')->first();
    expect($post)->not->toBeNull()
        ->and($post->tags)->toHaveCount(2);
});

test('tag names are trimmed and cleaned properly', function () {
    // Create a user and category
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create a post with tags that have whitespace
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Post with Whitespace Tags',
        'content' => 'Testing tag name cleaning.',
        'excerpt' => 'Tag cleaning test',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['  Laravel  ', ' Testing Framework ', 'Clean'],
    ]);

    // Assert response was successful
    $response->assertStatus(302);

    // Assert tags were created with clean names
    expect(Tag::count())->toBe(3)
        ->and(Tag::where('name', 'Laravel')->exists())->toBeTrue()
        ->and(Tag::where('name', 'Testing Framework')->exists())->toBeTrue()
        ->and(Tag::where('name', 'Clean')->exists())->toBeTrue()
        ->and(Tag::where('name', '  Laravel  ')->exists())->toBeFalse()
        ->and(Tag::where('name', ' Testing Framework ')->exists())->toBeFalse();

    // Verify no tags with whitespace exist
});

test('posts can be created without tags', function () {
    // Create a user and category
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create a post without any tags
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Post Without Tags',
        'content' => 'This post has no tags.',
        'excerpt' => 'No tags here',
        'type' => 'text',
        'category_id' => $category->id,
        // No tags field provided
    ]);

    // Assert response was successful
    $response->assertStatus(302);

    // Assert no tags were created
    expect(Tag::count())->toBe(0);

    // Verify the post was created without tags
    $post = Post::where('title', 'Post Without Tags')->first();
    expect($post)->not->toBeNull()
        ->and($post->tags)->toHaveCount(0);
});

test('duplicate tags in same post are handled correctly', function () {
    // Create a user and category
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create a post with duplicate tags
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Post with Duplicate Tags',
        'content' => 'Testing duplicate tag handling.',
        'excerpt' => 'Duplicate tags test',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['Laravel', 'Testing', 'Laravel', 'PHP', 'Testing'],
    ]);

    // Assert response was successful
    $response->assertStatus(302);

    // Assert unique tags were created (no duplicates)
    expect(Tag::count())->toBe(3)
        ->and(Tag::where('name', 'Laravel')->count())->toBe(1)
        ->and(Tag::where('name', 'Testing')->count())->toBe(1)
        ->and(Tag::where('name', 'PHP')->count())->toBe(1);

    // Verify the post has unique tags attached (duplicates consolidated)
    // Note: The deduplication system now removes duplicates automatically
    $post = Post::where('title', 'Post With Duplicate Tags')->first();
    expect($post->tags)->toHaveCount(3) // Only unique tags are attached
        ->and($post->tags->pluck('name')->unique())->toHaveCount(3); // All unique names
});
