<?php

namespace App\Models;

use App\Enums\FamilyRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's membership in a family, with the role they have there.
 *
 * @property int $id
 * @property int $family_id
 * @property int $user_id
 * @property FamilyRole $role
 */
class Membership extends Pivot
{
    protected $table = 'family_user';

    public $incrementing = true;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => FamilyRole::class,
        ];
    }
}
