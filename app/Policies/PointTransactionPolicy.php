<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\PointTransaction;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Only parents give or take points by hand, and say Godkänn to waiting points.
 */
class PointTransactionPolicy
{
    use ResolvesFamilyRole;

    public function create(User $user): bool
    {
        return $this->currentRole($user) === FamilyRole::Parent;
    }

    public function decide(User $user, PointTransaction $transaction): bool
    {
        return $transaction->pending
            && $this->roleIn($user, $transaction->family_id) === FamilyRole::Parent;
    }
}
