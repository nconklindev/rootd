<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function view(?User $user, Post $post): bool
    {
        // All posts are now viewable by everyone since there's no status
        return true;
    }

    public function create(User $user): bool
    {
        return $user !== null;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        $userRoles = $user->roles()->get();
        $isAdmin = in_array('admin', $userRoles->pluck('name')->toArray());

        return $user->id === $post->user_id || $isAdmin === true;
    }
}
