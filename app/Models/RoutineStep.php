<?php

namespace App\Models;

use App\Enums\StepType;
use Carbon\CarbonInterface;
use Database\Factories\RoutineStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A step in a routine. Always reached through its routine, which is scoped
 * to the family.
 *
 * @property int $id
 * @property int $routine_id
 * @property int|null $picture_id
 * @property string $label
 * @property int|null $minutes
 * @property StepType $type
 * @property list<int>|null $weekdays
 * @property bool $optional
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['picture_id', 'label', 'minutes', 'type', 'weekdays', 'optional', 'sort_order'])]
class RoutineStep extends Model
{
    /** @use HasFactory<RoutineStepFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StepType::class,
            'weekdays' => 'array',
            'optional' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Routine, $this>
     */
    public function routine(): BelongsTo
    {
        return $this->belongsTo(Routine::class);
    }

    /**
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    public function comesOn(CarbonInterface $date): bool
    {
        return $this->weekdays === null || $this->weekdays === [] || in_array($date->dayOfWeekIso, $this->weekdays, true);
    }
}
