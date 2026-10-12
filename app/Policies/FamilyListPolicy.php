<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\FamilyList;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class FamilyListPolicy
{
    use ResolvesFamilyRole;

    /**
     * Adults see every list. Children see the shopping list.
     */
    public function view(User $user, FamilyList $list): bool
    {
        $role = $this->roleIn($user, $list->family_id);

        return ($role?->isAdult() ?? false)
            || ($role === FamilyRole::Child && $list->isShopping());
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, FamilyList $list): bool
    {
        return $this->roleIn($user, $list->family_id)?->canEdit() ?? false;
    }

    /**
     * The shopping list stays. It can be renamed and emptied, but not removed.
     */
    public function delete(User $user, FamilyList $list): bool
    {
        return ! $list->isShopping() && $this->update($user, $list);
    }

    /**
     * Add items and tick them off. Children may do both on the shopping list.
     */
    public function addItem(User $user, FamilyList $list): bool
    {
        $role = $this->roleIn($user, $list->family_id);

        return ($role?->canEdit() ?? false)
            || ($role === FamilyRole::Child && $list->isShopping());
    }
}
