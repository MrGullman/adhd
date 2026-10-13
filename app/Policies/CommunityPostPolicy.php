<?php

namespace App\Policies;

use App\Models\CommunityPost;
use App\Models\User;

/**
 * Parents write and change their own posts and report others'.
 */
class CommunityPostPolicy
{
    public function update(User $user, CommunityPost $post): bool
    {
        return $post->user_id === $user->id && $this->canSee($user, $post);
    }

    public function delete(User $user, CommunityPost $post): bool
    {
        return $this->update($user, $post);
    }

    public function report(User $user, CommunityPost $post): bool
    {
        return $post->user_id !== $user->id && $this->canSee($user, $post);
    }

    private function canSee(User $user, CommunityPost $post): bool
    {
        $group = $post->thread?->group;

        return $group !== null && $user->can('view', $group);
    }
}
