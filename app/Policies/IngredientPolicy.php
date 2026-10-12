<?php

namespace App\Policies;

use App\Models\Ingredient;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class IngredientPolicy
{
    use ResolvesFamilyRole;

    /**
     * The basic list is for everyone, the family's own only for the family.
     */
    public function view(User $user, Ingredient $ingredient): bool
    {
        return $ingredient->family_id === null || $this->roleIn($user, $ingredient->family_id) !== null;
    }

    /**
     * The family adds its own ingredients by typing a new name.
     */
    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }
}
