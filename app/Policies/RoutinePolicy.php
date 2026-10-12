<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\Routine;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class RoutinePolicy
{
    use ResolvesFamilyRole;

    /**
     * Adults see the family's routines. A child sees the routines they do.
     */
    public function view(User $user, Routine $routine): bool
    {
        if ($routine->family_id === null) {
            return false;
        }

        $role = $this->roleIn($user, $routine->family_id);

        return ($role?->isAdult() ?? false)
            || ($role === FamilyRole::Child && $routine->children()->whereKey($user->id)->exists());
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, Routine $routine): bool
    {
        return $routine->family_id !== null
            && ($this->roleIn($user, $routine->family_id)?->canEdit() ?? false);
    }

    public function delete(User $user, Routine $routine): bool
    {
        return $this->update($user, $routine);
    }

    /**
     * Tick off steps: the child doing the routine.
     */
    public function doSteps(User $user, Routine $routine): bool
    {
        return $routine->family_id !== null
            && $this->roleIn($user, $routine->family_id) === FamilyRole::Child
            && $routine->children()->whereKey($user->id)->exists();
    }
}
