<?php

namespace App\Policies;

use App\Models\MealPlanEntry;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class MealPlanEntryPolicy
{
    use ResolvesFamilyRole;

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, MealPlanEntry $entry): bool
    {
        return $this->roleIn($user, $entry->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, MealPlanEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
