<?php

namespace App\Policies;

use App\Models\ListItem;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class ListItemPolicy
{
    use ResolvesFamilyRole;

    /**
     * Tick an item off or back on: whoever may add to the list.
     */
    public function check(User $user, ListItem $item): bool
    {
        return $user->can('addItem', $item->list);
    }

    public function update(User $user, ListItem $item): bool
    {
        return $this->roleIn($user, $item->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, ListItem $item): bool
    {
        return $this->update($user, $item);
    }
}
