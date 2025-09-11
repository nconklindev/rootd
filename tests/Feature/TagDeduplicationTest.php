<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

uses(RefreshDatabase::class, WithoutMiddleware::class);

test('similar tags are automatically consolidated during post creation', function () {
    // Create test data
    $user = User::factory()->create();
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'color' => '#3B82F6',
        'description' => 'Test category description',
    ]);

    // Create an existing "Laravel" tag
    Tag::create([
        'name' => 'Laravel',
        'slug' => 'laravel',
        'color' => '#FF0000',
    ]);

    expect(Tag::count())->toBe(1);

    // Try to create a post with "Laravel Framework" (should use the existing "Laravel" tag)
    $response = $this->actingAs($user)->post('/posts', [
        'title' => 'Advanced Laravel Development',
        'content' => 'Deep dive into Laravel framework features.',
        'excerpt' => 'Laravel framework tutorial',
        'type' => 'text',
        'category_id' => $category->id,
        'tags' => ['Laravel Framework', 'PHP', 'Web Development'],
    ]);

    $response->assertStatus(302);

    // Should still only have 3 tags total (Laravel Framework -> Laravel, PHP, Web Development)
    expect(Tag::count())->toBe(3)
        ->and(Tag::where('name', 'Laravel Framework')->exists())->toBeFalse()
        ->and(Tag::where('name', 'Laravel')->exists())->toBeTrue();

    // Verify the post uses the existing Laravel tag
    $post = Post::where('title', 'Advanced Laravel Development')->first();
    $tagNames = $post->tags->pluck('name')->toArray();
    expect($tagNames)->toContain('Laravel')
        ->and($tagNames)->not->toContain('Laravel Framework');
});

test('tag similarity detection works correctly', function () {
    // Create test tags
    Tag::create(['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#FF0000']);
    Tag::create(['name' => 'Vue.js', 'slug' => 'vue-js', 'color' => '#00FF00']);
    Tag::create(['name' => 'JavaScript', 'slug' => 'javascript', 'color' => '#0000FF']);

    // Test exact matches and variations
    $laravelSimilar = Tag::findSimilarTags('Laravel Framework', 0.7);
    expect($laravelSimilar)->toHaveCount(1)
        ->and($laravelSimilar->first()->name)->toBe('Laravel');

    $vueSimilar = Tag::findSimilarTags('Vue', 0.6); // Lower threshold
    expect($vueSimilar)->toHaveCount(1)
        ->and($vueSimilar->first()->name)->toBe('Vue.js');

    $jsSimilar = Tag::findSimilarTags('JS', 0.7);
    expect($jsSimilar)->toHaveCount(1)
        ->and($jsSimilar->first()->name)->toBe('JavaScript');

    // Test typos
    $typoSimilar = Tag::findSimilarTags('Laravell', 0.7);
    expect($typoSimilar)->toHaveCount(1)
        ->and($typoSimilar->first()->name)->toBe('Laravel');
});

test('tag normalization removes common suffixes and prefixes', function () {
    expect(Tag::normalizeTagName('Laravel Framework'))->toBe('laravel')
        ->and(Tag::normalizeTagName('Vue.js Library'))->toBe('vue.js')
        ->and(Tag::normalizeTagName('The React Framework'))->toBe('react')
        ->and(Tag::normalizeTagName('JavaScript Lang'))->toBe('javascript');
});

test('tag merging functionality works correctly', function () {
    // Create test data
    $user = User::factory()->create();

    // Create two similar tags
    $tag1 = Tag::create(['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#FF0000']);
    $tag2 = Tag::create(['name' => 'Laravel Framework', 'slug' => 'laravel-framework', 'color' => '#00FF00']);

    // Create posts with each tag
    $post1 = Post::factory()->create(['user_id' => $user->id]);
    $post2 = Post::factory()->create(['user_id' => $user->id]);

    $post1->tags()->attach($tag1->id);
    $post2->tags()->attach($tag2->id);

    expect(Tag::count())->toBe(2)
        ->and($post1->tags)->toHaveCount(1)
        ->and($post2->tags)->toHaveCount(1);

    // Merge tag2 into tag1
    $tag2->mergeWith($tag1);

    // Verify merge results
    expect(Tag::count())->toBe(1)
        ->and(Tag::where('name', 'Laravel Framework')->exists())->toBeFalse()
        ->and(Tag::where('name', 'Laravel')->exists())->toBeTrue();

    // Verify all posts now use the target tag
    $post1->refresh();
    $post2->refresh();

    expect($post1->tags->first()->name)->toBe('Laravel')
        ->and($post2->tags->first()->name)->toBe('Laravel');
});

test('tag suggestions include popular tags', function () {
    // Create tags with different usage patterns
    Tag::create(['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#FF0000']);
    Tag::create(['name' => 'PHP', 'slug' => 'php', 'color' => '#00FF00']);
    Tag::create(['name' => 'Laravel Framework', 'slug' => 'laravel-framework', 'color' => '#0000FF']);

    // Test suggestions for "laravel"
    $suggestions = Tag::suggestTags('laravel');

    expect($suggestions)->toHaveCount(2)
        ->and($suggestions->first()->name)->toBe('Laravel'); // Laravel and Laravel Framework
    // Most similarly should come first
});

test('empty and short inputs return appropriate responses', function () {
    Tag::create(['name' => 'Laravel', 'slug' => 'laravel', 'color' => '#FF0000']);

    // Empty input
    $emptyResult = Tag::findSimilarTags('');
    expect($emptyResult)->toHaveCount(0);

    // Single character
    $shortResult = Tag::findSimilarTags('L', 0.8);
    expect($shortResult)->toHaveCount(0);

    // Two characters should work
    $twoCharResult = Tag::suggestTags('La');
    expect($twoCharResult)->toHaveCount(1);
});
