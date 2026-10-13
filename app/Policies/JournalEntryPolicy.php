<?php

namespace App\Policies;

use App\Enums\FamilyRole;
use App\Models\JournalEntry;
use App\Models\User;
use App\Policies\Concerns\ResolvesFamilyRole;

/**
 * Dagboken is for parents only: never other adults, never children.
 */
class JournalEntryPolicy
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

    public function update(User $user, JournalEntry $entry): bool
    {
        return $this->roleIn($user, $entry->family_id) === FamilyRole::Parent;
    }

    public function delete(User $user, JournalEntry $entry): bool
    {
        return $this->update($user, $entry);
    }
}
