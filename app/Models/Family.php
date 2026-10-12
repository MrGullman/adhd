<?php

namespace App\Models;

use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Enums\ListType;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property array<string, mixed>|null $settings
 * @property array<string, string>|null $day_parts
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
            'day_parts' => 'array',
        ];
    }

    /**
     * Every family has a shopping list from the start.
     */
    protected static function booted(): void
    {
        static::created(function (Family $family): void {
            $family->lists()->create(['type' => ListType::Shopping, 'name' => 'Handlingslista']);
        });
    }

    /**
     * @return HasMany<FamilyList, $this>
     */
    public function lists(): HasMany
    {
        return $this->hasMany(FamilyList::class);
    }

    /**
     * When each part of the day starts (HH:MM), in order from morning.
     *
     * @return array<string, string>
     */
    public function dayParts(): array
    {
        $starts = [];

        foreach (DayPart::cases() as $part) {
            $start = $this->day_parts[$part->value] ?? null;
            $starts[$part->value] = is_string($start) ? $start : $part->defaultStart();
        }

        return $starts;
    }

    /**
     * The part of the day a time belongs to. Times before the morning count as morning.
     */
    public function dayPartFor(string $time): DayPart
    {
        $time = substr($time, 0, 5);
        $result = DayPart::Morning;

        foreach ($this->dayParts() as $part => $start) {
            if ($time >= $start) {
                $result = DayPart::from($part);
            }
        }

        return $result;
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
            ->withPivot('role', 'school_menu_id')
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
     * The school a member eats at, if any.
     */
    public function schoolMenuIdOf(User $member): ?int
    {
        $id = Membership::query()
            ->where('family_id', $this->id)
            ->where('user_id', $member->id)
            ->value('school_menu_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * A child in this family, or a 404 so that other families' children stay invisible.
     */
    public function findChildOrFail(int $id): User
    {
        return $this->children()->findOrFail($id);
    }
}
