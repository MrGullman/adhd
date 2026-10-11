<?php

namespace App\Policies\Concerns;

use App\Enums\FamilyRole;
use App\Models\Membership;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Support\Facades\Auth;

trait ResolvesFamilyRole
{
    /**
     * The user's role in the family that owns a record. Within the current
     * family the role resolved for this request is reused, so that lists with
     * many rows do not ask the database once per row.
     */
    protected function roleIn(User $user, int $familyId): ?FamilyRole
    {
        $current = app(CurrentFamily::class);

        if ($current->id() === $familyId && $user->is(Auth::user())) {
            return $current->role();
        }

        $membership = $user->families()->whereKey($familyId)->first()?->getRelation('pivot');

        return $membership instanceof Membership ? $membership->role : null;
    }

    /**
     * The user's role in the current family, for actions without a record yet.
     */
    protected function currentRole(User $user): ?FamilyRole
    {
        $familyId = app(CurrentFamily::class)->id();

        return $familyId === null ? null : $this->roleIn($user, $familyId);
    }
}
