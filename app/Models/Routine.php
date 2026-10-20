<?php

namespace App\Models;

use App\Enums\DayPart;
use App\Enums\Weeks;
use App\Models\Scopes\FamilyScope;
use Carbon\CarbonInterface;
use Database\Factories\RoutineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A chain of steps with pictures, e.g. the morning routine. family_id null
 * is a template from the admin portal, which families copy.
 *
 * Scoped to the current family like BelongsToFamily, but family_id is never
 * filled in automatically: a template must stay without a family even when
 * an admin who is also a parent creates it.
 *
 * @property int $id
 * @property int|null $family_id
 * @property int|null $picture_id
 * @property string $name
 * @property DayPart|null $day_part
 * @property string|null $start_time
 * @property string|null $done_by
 * @property list<int>|null $weekdays
 * @property Weeks $weeks
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property bool $ordered
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['picture_id', 'name', 'day_part', 'start_time', 'done_by', 'weekdays', 'weeks', 'starts_on', 'ends_on', 'ordered'])]
#[ScopedBy([FamilyScope::class])]
class Routine extends Model
{
    /** @use HasFactory<RoutineFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_part' => DayPart::class,
            'weekdays' => 'array',
            'weeks' => Weeks::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'ordered' => 'boolean',
        ];
    }

    /**
     * The templates from the admin portal. They have no family, so they are
     * read past the family scope. Nothing else is.
     *
     * @return Builder<Routine>
     */
    public static function templates(): Builder
    {
        return self::query()->withoutGlobalScope(FamilyScope::class)->whereNull('family_id');
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    /**
     * The children who do the routine.
     *
     * @return BelongsToMany<User, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'routine_child');
    }

    /**
     * @return HasMany<RoutineStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RoutineStep::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<RoutineRun, $this>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(RoutineRun::class);
    }

    public function runsOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return ($this->weekdays === null || $this->weekdays === [] || in_array($date->dayOfWeekIso, $this->weekdays, true))
            && ($this->weeks ?? Weeks::All)->includes($date)
            && ($this->starts_on === null || $this->starts_on->toDateString() <= $day)
            && ($this->ends_on === null || $this->ends_on->toDateString() >= $day);
    }

    /**
     * The steps done on a day: some steps only come on certain weekdays.
     *
     * @return Collection<int, RoutineStep>
     */
    public function stepsOn(CarbonInterface $date): Collection
    {
        return $this->steps->filter(fn (RoutineStep $step) => $step->comesOn($date))->values();
    }

    /**
     * Time as HH:MM, e.g. "07:45".
     */
    public static function short(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }

    /**
     * Copy the routine with its steps into a family, e.g. a template.
     * The copy is the family's own: changes never go back to the template.
     */
    public function copyTo(Family $family, ?User $creator): self
    {
        return DB::transaction(function () use ($family, $creator): self {
            $copy = $this->replicate(['family_id', 'created_by']);
            $copy->family_id = $family->id;
            $copy->created_by = $creator?->id;
            $copy->save();

            foreach ($this->steps as $step) {
                $copy->steps()->save($step->replicate(['routine_id']));
            }

            return $copy;
        });
    }
}
