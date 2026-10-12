<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class RecipePolicy
{
    use ResolvesFamilyRole;

    public function view(User $user, Recipe $recipe): bool
    {
        return $this->roleIn($user, $recipe->family_id)?->isAdult() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $this->roleIn($user, $recipe->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $this->update($user, $recipe);
    }
}
