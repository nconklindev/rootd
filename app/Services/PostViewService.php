<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Number;

readonly class PostViewService
{
    public function __construct(
        private Request $request
    )
    {
    }

    /**
     * Record a view for the given post, preventing duplicate views.
     * Uses session-based tracking to prevent the same user from inflating view counts.
     */
    public function recordView(Post $post): bool
    {
        $sessionKey = $this->getSessionKey($post);

        // Check if this user/session has already viewed this post
        if ($this->hasAlreadyViewed($sessionKey)) {
            return false; // Already viewed, don't increment
        }

        // Record the view in session
        $this->markAsViewed($sessionKey);

        // Increment the view count atomically
        $this->incrementViewCount($post);

        return true;
    }

    /**
     * Generate a unique session key for tracking views.
     */
    private function getSessionKey(Post $post): string
    {
        $userId = $this->request->user()?->id ?? 'anonymous';
        $sessionId = $this->request->session()->getId();

        return "post_view_{$post->id}_{$userId}_{$sessionId}";
    }

    /**
     * Check if the current user/session has already viewed this post.
     */
    private function hasAlreadyViewed(string $sessionKey): bool
    {
        return $this->request->session()->has($sessionKey);
    }

    /**
     * Mark this post as viewed in the current session.
     * Store with a reasonable expiration (24 hours) to prevent session bloat.
     */
    private function markAsViewed(string $sessionKey): void
    {
        $this->request->session()->put($sessionKey, now()->timestamp);

        // Clean up old view records (older than 24 hours) to prevent session bloat
        $this->cleanupOldViewRecords();
    }

    /**
     * Clean up old view records from the session to prevent bloat.
     * Remove records older than 24 hours.
     */
    private function cleanupOldViewRecords(): void
    {
        $session = $this->request->session();
        $cutoff = now()->subDay()->timestamp;

        // Get all session keys that start with 'post_view_'
        $allSessionData = $session->all();

        foreach ($allSessionData as $key => $value) {
            if (str_starts_with($key, 'post_view_') && is_numeric($value) && $value < $cutoff) {
                $session->forget($key);
            }
        }
    }

    private function incrementViewCount(Post $post): void
    {
        Post::where('id', '=', $post->id)->increment('views_count');

        // Update the model instance to reflect the new count
        $post->refresh();
    }

    public function getViewCount(Post $post): int
    {
        return $post->views_count ?? 0;
    }
}
