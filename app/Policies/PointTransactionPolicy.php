<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Only parents give or take points by hand.
 */
class PointTransactionPolicy
{
    use ResolvesFamilyRole;

    public function create(User $user): bool
    {
        return $this->currentRole($user) === FamilyRole::Parent;
    }
}
