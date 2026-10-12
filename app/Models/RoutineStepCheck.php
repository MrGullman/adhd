<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A step done, or skipped, in a run.
 *
 * @property int $id
 * @property int $routine_run_id
 * @property int $routine_step_id
 * @property Carbon $checked_at
 * @property bool $skipped
 */
#[Fillable(['routine_step_id', 'checked_at', 'skipped'])]
#[WithoutTimestamps]
class RoutineStepCheck extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'skipped' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<RoutineRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(RoutineRun::class, 'routine_run_id');
    }
}
