<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    /**
     * Members see their family. Children see only their own pages, which are
     * guarded by route middleware.
     */
    public function view(User $user, Family $family): bool
    {
        return $user->roleIn($family)?->isAdult() ?? false;
    }

    public function update(User $user, Family $family): bool
    {
        return $user->roleIn($family)?->canManage() ?? false;
    }

    /**
     * Invite adults and manage children, their codes and devices.
     */
    public function manageMembers(User $user, Family $family): bool
    {
        return $user->roleIn($family)?->canManage() ?? false;
    }

    /**
     * Make the family the user's active family.
     */
    public function switchTo(User $user, Family $family): bool
    {
        return $user->roleIn($family)?->isAdult() ?? false;
    }
}
