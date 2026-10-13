<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Enums\RedemptionStatus;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * A parent answers a child's wish, once.
 */
class RewardRedemptionPolicy
{
    use ResolvesFamilyRole;

    public function decide(User $user, RewardRedemption $redemption): bool
    {
        return $redemption->status === RedemptionStatus::Requested
            && $this->roleIn($user, $redemption->family_id) === FamilyRole::Parent;
    }
}
