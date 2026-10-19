<?php

namespace App\Policies;

use App\Models\ActivityCancellation;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Adults who may change the planning cancel an occurrence, and undo it.
 */
class ActivityCancellationPolicy
{
    use ResolvesFamilyRole;

    public function delete(User $user, ActivityCancellation $cancellation): bool
    {
        return $this->roleIn($user, $cancellation->family_id)?->canEdit() ?? false;
    }
}
