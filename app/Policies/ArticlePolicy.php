<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Om NPF is public: anyone reads what is published. Only admins write, in the admin portal.
 */
class ArticlePolicy
{
    public function view(?User $user, Article $article): bool
    {
        return $article->published || ($user !== null && $user->is_admin);
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Article $article): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->is_admin;
    }
}
