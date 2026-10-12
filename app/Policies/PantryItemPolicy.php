<?php

namespace App\Policies;

use App\Models\PantryItem;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class PantryItemPolicy
{
    use ResolvesFamilyRole;

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, PantryItem $item): bool
    {
        return $this->roleIn($user, $item->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, PantryItem $item): bool
    {
        return $this->update($user, $item);
    }
}
