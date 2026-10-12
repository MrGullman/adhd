<?php

namespace App\Policies;

use App\Models\RecurringTask;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

class RecurringTaskPolicy
{
    use ResolvesFamilyRole;

    public function view(User $user, RecurringTask $recurring): bool
    {
        return $this->roleIn($user, $recurring->family_id)?->isAdult() ?? false;
    }

    public function create(User $user): bool
    {
        return $this->currentRole($user)?->canEdit() ?? false;
    }

    public function update(User $user, RecurringTask $recurring): bool
    {
        return $this->roleIn($user, $recurring->family_id)?->canEdit() ?? false;
    }

    public function delete(User $user, RecurringTask $recurring): bool
    {
        return $this->update($user, $recurring);
    }
}
