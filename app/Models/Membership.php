<?php

namespace App\Models;

use App\Enums\FamilyRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A user's membership in a family, with the role they have there.
 *
 * @property int $id
 * @property int $family_id
 * @property int $user_id
 * @property FamilyRole $role
 * @property int|null $school_menu_id
 * @property bool $rewards_enabled
 * @property bool $all_recipes
 * @property bool $child_reminders
 * @property list<int>|null $child_reminder_ids Null: every child.
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
            'rewards_enabled' => 'boolean',
            'all_recipes' => 'boolean',
            'child_reminders' => 'boolean',
            'child_reminder_ids' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
