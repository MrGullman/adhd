<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\Calendar;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Only parents add and change calendars (docs/ARCHITECTURE.md, Kalender).
 */
class CalendarPolicy
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

    public function update(User $user, Calendar $calendar): bool
    {
        return $this->roleIn($user, $calendar->family_id) === FamilyRole::Parent;
    }

    public function delete(User $user, Calendar $calendar): bool
    {
        return $this->update($user, $calendar);
    }
}
