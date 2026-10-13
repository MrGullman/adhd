<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\CommunityGroup;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Community is for parents only (docs/ARCHITECTURE.md). Other adults and
 * children never reach it. Reading and writing in a group needs membership.
 */
class CommunityGroupPolicy
{
    use ResolvesFamilyRole;

    public function viewAny(User $user): bool
    {
        return $this->currentRole($user) === FamilyRole::Parent;
    }

    public function join(User $user, CommunityGroup $group): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, CommunityGroup $group): bool
    {
        return $this->viewAny($user) && $group->members()->whereKey($user->id)->exists();
    }

    /**
     * A new thread, under the name chosen for Community.
     */
    public function post(User $user, CommunityGroup $group): bool
    {
        return $user->community_name !== null && $this->view($user, $group);
    }
}
