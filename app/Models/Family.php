<?php

namespace App\Models;

use App\Enums\FamilyRole;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name'])]
class Family extends Model
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Everyone in the family, adults and children.
     *
     * @return BelongsToMany<User, $this, Membership>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(Membership::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this, Membership>
     */
    public function adults(): BelongsToMany
    {
        return $this->members()->wherePivotIn(
            'role',
            array_map(fn (FamilyRole $role) => $role->value, FamilyRole::adultRoles()),
        );
    }

    /**
     * @return BelongsToMany<User, $this, Membership>
     */
    public function children(): BelongsToMany
    {
        return $this->members()->wherePivot('role', FamilyRole::Child->value);
    }

    public function addMember(User $user, FamilyRole $role): void
    {
        $this->members()->attach($user, ['role' => $role->value]);
    }

    /**
     * A child in this family, or a 404 so that other families' children stay invisible.
     */
    public function findChildOrFail(int $id): User
    {
        return $this->children()->findOrFail($id);
    }
}
