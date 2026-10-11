<?php

namespace App\Policies;

use App\Models\FamilyInvitation;
use App\Models\User;

class FamilyInvitationPolicy
{
    public function delete(User $user, FamilyInvitation $invitation): bool
    {
        return $invitation->family !== null
            && ($user->roleIn($invitation->family)?->canManage() ?? false);
    }
}
