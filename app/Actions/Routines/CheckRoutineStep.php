<?php

namespace App\Actions\Routines;

use App\Models\Routine;
use App\Models\RoutineRun;
use App\Models\RoutineStep;
use App\Models\User;
use App\Points\Points;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use Illuminate\Support\Facades\DB;

/**
 * A child ticks off (or skips) a step in today's run of a routine. The run
 * is started at the first step and completed when every step of the day is
 * done or skipped.
 */
class CheckRoutineStep
{
    public function __construct(private Points $points) {}

    public function check(Routine $routine, User $child, RoutineStep $step, bool $skipped = false): void
    {
        DB::transaction(function () use ($routine, $child, $step, $skipped): void {
            $run = RoutineRun::query()->firstOrCreate(
                ['routine_id' => $routine->id, 'user_id' => $child->id, 'date' => LocalTime::today()],
                ['started_at' => now()],
            );

            $run->checks()->firstOrCreate(
                ['routine_step_id' => $step->id],
                ['checked_at' => now(), 'skipped' => $skipped],
            );

            $this->updateCompletion($routine, $child, $run);
        });
    }

    public function uncheck(Routine $routine, User $child, RoutineStep $step): void
    {
        $run = RoutineDay::for($routine, $child)->run;

        if ($run === null) {
            return;
        }

        $run->checks()->where('routine_step_id', $step->id)->delete();

        $this->updateCompletion($routine, $child, $run);
    }

    private function updateCompletion(Routine $routine, User $child, RoutineRun $run): void
    {
        $completed = RoutineDay::for($routine, $child)->isCompleted();

        $run->forceFill(['completed_at' => $completed ? ($run->completed_at ?? now()) : null])->save();

        if ($completed) {
            $this->points->award($child, 'routine', $run->id, $routine->name);
        } else {
            $this->points->revoke('routine', $run->id);
        }
    }
}
