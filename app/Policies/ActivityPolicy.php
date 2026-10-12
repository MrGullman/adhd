<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class ActivityPolicy
{
    use ResolvesFamilyRole;

    /**
     * Adults see everything. A child sees what they take part in and what is
     * marked "Visa för barnen".
     */
    public function view(User $user, Activity $activity): bool
    {
        $role = $this->roleIn($user, $activity->family_id);

        return ($role?->isAdult() ?? false)
            || ($role === FamilyRole::Child && $activity->isVisibleTo($user));
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return $this->roleIn($user, $activity->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }

    /**
     * Pack and answer "Ja, jag har packat": a child who goes.
     */
    public function pack(User $user, Activity $activity): bool
    {
        return $this->roleIn($user, $activity->family_id) === FamilyRole::Child
            && $activity->participants()->whereKey($user->id)->exists();
    }
}
