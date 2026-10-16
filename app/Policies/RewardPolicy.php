<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\Reward;
use App\Models\User;
use App\Points\Points;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Parents decide about rewards and points. Other adults never give points,
 * and children only wish.
 */
class RewardPolicy
{
    use ResolvesFamilyRole;

    public function viewAny(User $user): bool
    {
        return $this->currentRole($user) === FamilyRole::Parent;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Reward $reward): bool
    {
        return $this->roleIn($user, $reward->family_id) === FamilyRole::Parent;
    }

    public function delete(User $user, Reward $reward): bool
    {
        return $this->update($user, $reward);
    }

    public function wish(User $user, Reward $reward): bool
    {
        return $reward->active
            && $this->roleIn($user, $reward->family_id) === FamilyRole::Child
            && Points::enabledFor($reward->family, $user);
    }
}
