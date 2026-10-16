<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Food\ChildRecipes;
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

    /**
     * Laga: the cooking view. Adults cook every recipe, children the week's
     * dinners, or every recipe when a parent has chosen that.
     */
    public function cook(User $user, Recipe $recipe): bool
    {
        $role = $this->roleIn($user, $recipe->family_id);

        if ($role?->isAdult() ?? false) {
            return true;
        }

        return $role === FamilyRole::Child
            && (ChildRecipes::allowsAll($recipe->family_id, $user) || ChildRecipes::inWeek($recipe->id));
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
