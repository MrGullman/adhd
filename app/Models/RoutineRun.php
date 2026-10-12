<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\RoutineRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A child doing a routine on a day.
 *
 * @property int $id
 * @property int $family_id
 * @property int $routine_id
 * @property int $user_id
 * @property Carbon $date
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['routine_id', 'user_id', 'date', 'started_at'])]
class RoutineRun extends Model
{
    /** @use HasFactory<RoutineRunFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RoutineStepCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(RoutineStepCheck::class);
    }
}
