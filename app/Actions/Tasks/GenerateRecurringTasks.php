<?php

namespace App\Actions\Tasks;

use App\Models\Family;
use App\Models\RecurringTask;
use App\Models\Task;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Creates the days a recurring task occurs on as tasks, a week ahead, so that
 * Imorgon and Veckan can be planned. Runs every night and when a recurring
 * task is saved. Runs inside the current family (BelongsToFamily).
 */
class GenerateRecurringTasks
{
    /**
     * Days ahead that get their tasks, today included.
     */
    public const DAYS_AHEAD = 7;

    /**
     * Create the missing occurrences from a day on (default today in Sweden).
     * A day that already has an occurrence, also a skipped one, is left alone,
     * so running this twice never makes duplicates.
     *
     * @return int The number of tasks created.
     */
    public function handle(Family $family, ?RecurringTask $only = null, ?CarbonImmutable $from = null): int
    {
        $from = ($from ?? LocalTime::now())->startOfDay();
        $last = $from->addDays(self::DAYS_AHEAD - 1);

        $recurringTasks = $only !== null
            ? collect([$only])
            : RecurringTask::query()
                ->whereDate('starts_on', '<=', $last->toDateString())
                ->where(fn ($query) => $query
                    ->whereNull('ends_on')
                    ->orWhereDate('ends_on', '>=', $from->toDateString()))
                ->get();

        $created = 0;

        foreach ($recurringTasks as $recurring) {
            $taken = $recurring->occurrences()
                ->withTrashed()
                ->whereBetween('date', [$from->toDateString(), $last->toDateString()])
                ->get(['date'])
                ->map(fn (Task $task) => $task->date->toDateString())
                ->all();

            for ($date = $from; $date->lte($last); $date = $date->addDay()) {
                if (in_array($date->toDateString(), $taken, true) || ! $recurring->occursOn($date)) {
                    continue;
                }

                try {
                    $this->createOccurrence($family, $recurring, $date);
                    $created++;
                } catch (UniqueConstraintViolationException) {
                    // Created by another run at the same time.
                }
            }
        }

        return $created;
    }

    /**
     * After a recurring task is changed: coming days (from tomorrow) are
     * created again from the new settings. Days that are ticked off, changed
     * by hand or skipped stay as they are.
     */
    public function refresh(Family $family, RecurringTask $recurring): void
    {
        $this->removeComingOccurrences($recurring);

        $this->handle($family, $recurring);
    }

    /**
     * Remove the coming days that nobody has touched, e.g. when the recurring task is removed.
     */
    public function removeComingOccurrences(RecurringTask $recurring): void
    {
        $recurring->occurrences()
            ->whereDate('date', '>', LocalTime::today())
            ->whereNull('completed_at')
            ->where('customized', false)
            ->get()
            ->each(fn (Task $task) => $task->forceDelete());
    }

    private function createOccurrence(Family $family, RecurringTask $recurring, CarbonImmutable $date): void
    {
        $task = new Task([
            'title' => $recurring->title,
            'picture_id' => $recurring->picture_id,
            'assigned_to' => $recurring->assigned_to,
            'date' => $date->toDateString(),
            'day_part' => $recurring->time !== null ? $family->dayPartFor($recurring->time) : $recurring->day_part,
            'time' => $recurring->time,
            'created_by' => $recurring->created_by,
        ]);
        $task->recurring_task_id = $recurring->id;
        $task->save();
    }
}
