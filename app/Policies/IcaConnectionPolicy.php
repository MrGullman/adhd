<?php

namespace App\Policies;

use App\Models\IcaConnection;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * The ICA connection is set up and removed by parents, since it logs in
 * with one of their ICA accounts.
 */
class IcaConnectionPolicy
{
    use ResolvesFamilyRole;

    public function manage(User $user): bool
    {
        return $this->currentRole($user)?->canManage() ?? false;
    }

    public function update(User $user, IcaConnection $connection): bool
    {
        return $this->roleIn($user, $connection->family_id)?->canManage() ?? false;
    }

    public function delete(User $user, IcaConnection $connection): bool
    {
        return $this->update($user, $connection);
    }
}
