<?php

namespace App\Support;

use App\Enums\DayPart;
use App\Models\Family;
use App\Models\Routine;
use App\Models\RoutineRun;
use App\Models\RoutineStep;
use App\Models\RoutineStepCheck;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * One child's routine on one day: which steps come today, what is done,
 * what to do now and how much time is left.
 */
class RoutineDay
{
    /**
     * @var Collection<int, RoutineStep>
     */
    private Collection $steps;

    /**
     * @var array<int, RoutineStepCheck>
     */
    private array $checks = [];

    public function __construct(
        public readonly Routine $routine,
        public readonly User $child,
        public readonly CarbonImmutable $date,
        public readonly ?RoutineRun $run,
    ) {
        $this->steps = $routine->stepsOn($date);

        foreach ($run === null ? [] : $run->checks as $check) {
            $this->checks[$check->routine_step_id] = $check;
        }
    }

    public static function for(Routine $routine, User $child, ?CarbonImmutable $date = null): self
    {
        $date ??= LocalTime::now()->startOfDay();

        $run = RoutineRun::query()
            ->with('checks')
            ->where('routine_id', $routine->id)
            ->where('user_id', $child->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        return new self($routine, $child, $date, $run);
    }

    /**
     * @return Collection<int, RoutineStep>
     */
    public function steps(): Collection
    {
        return $this->steps;
    }

    public function check(RoutineStep $step): ?RoutineStepCheck
    {
        return $this->checks[$step->id] ?? null;
    }

    /**
     * The first step not done or skipped, in the routine's order.
     */
    public function currentStep(): ?RoutineStep
    {
        return $this->steps->first(fn (RoutineStep $step) => $this->check($step) === null);
    }

    public function isCompleted(): bool
    {
        return $this->steps->isNotEmpty() && $this->currentStep() === null;
    }

    /**
     * @return array{total: int, completed: int}
     */
    public function progress(): array
    {
        return [
            'total' => $this->steps->count(),
            'completed' => $this->steps->filter(fn (RoutineStep $step) => $this->check($step) !== null)->count(),
        ];
    }

    /**
     * Estimated minutes for the steps not done yet.
     */
    public function minutesNeeded(): int
    {
        return (int) $this->steps
            ->filter(fn (RoutineStep $step) => $this->check($step) === null)
            ->sum(fn (RoutineStep $step) => $step->minutes ?? 0);
    }

    /**
     * Minutes until "klar senast", or null when the routine has no such time.
     * Never below zero.
     */
    public function minutesLeft(CarbonImmutable $now): ?int
    {
        if ($this->routine->done_by === null) {
            return null;
        }

        $doneBy = CarbonImmutable::parse($this->date->toDateString().' '.$this->routine->done_by, LocalTime::ZONE);

        return max(0, (int) floor($now->diffInMinutes($doneBy, false)));
    }

    /**
     * Optional steps left to skip when the time is not enough for everything.
     *
     * @return Collection<int, RoutineStep>
     */
    public function skippable(CarbonImmutable $now): Collection
    {
        $left = $this->minutesLeft($now);

        if ($left === null || $this->minutesNeeded() <= $left) {
            return new Collection;
        }

        return $this->steps
            ->filter(fn (RoutineStep $step) => $step->optional && $this->check($step) === null)
            ->values();
    }

    /**
     * Time to do the routine: its part of the day is now, or it has started
     * and "klar senast" has not passed by more than an hour.
     */
    public function isDue(Family $family, CarbonImmutable $now): bool
    {
        if ($this->isCompleted() || $this->steps->isEmpty()) {
            return false;
        }

        $time = $now->format('H:i');
        $start = Routine::short($this->routine->start_time);
        $doneBy = Routine::short($this->routine->done_by);

        if ($start !== null) {
            $until = $doneBy === null ? '23:59' : CarbonImmutable::parse($doneBy)->addHour()->format('H:i');

            return $time >= $start && $time <= $until;
        }

        return $this->routine->day_part !== null
            && $this->routine->day_part === $family->dayPartFor($time);
    }

    /**
     * Short summary for Idag, for the child and the adults.
     *
     * @return array<string, mixed>
     */
    public function summary(Family $family, CarbonImmutable $now): array
    {
        return [
            'id' => $this->routine->id,
            'name' => $this->routine->name,
            'picture' => $this->routine->picture?->toPicker(),
            'doneBy' => Routine::short($this->routine->done_by),
            'dayPart' => $this->routine->day_part?->value,
            'progress' => $this->progress(),
            'completed' => $this->isCompleted(),
            'due' => $this->isDue($family, $now),
            'currentStep' => $this->currentStep()?->label,
            'child' => [
                'id' => $this->child->id,
                'name' => $this->child->name,
                'avatarColor' => $this->child->avatar_color->value,
                'avatarUrl' => $this->child->avatarUrl(),
            ],
        ];
    }

    /**
     * The whole routine for the child's focus mode or checklist.
     *
     * @return array<string, mixed>
     */
    public function focus(CarbonImmutable $now): array
    {
        $current = $this->currentStep();
        $next = $current === null ? null : $this->steps
            ->skipUntil(fn (RoutineStep $step) => $step->is($current))
            ->skip(1)
            ->first(fn (RoutineStep $step) => $this->check($step) === null);

        return [
            'id' => $this->routine->id,
            'name' => $this->routine->name,
            'ordered' => $this->routine->ordered,
            'doneBy' => Routine::short($this->routine->done_by),
            'minutesLeft' => $this->minutesLeft($now),
            'minutesNeeded' => $this->minutesNeeded(),
            'progress' => $this->progress(),
            'completed' => $this->isCompleted(),
            'currentStepId' => $current?->id,
            'nextStepId' => $next?->id,
            'skippable' => $this->skippable($now)->map(fn (RoutineStep $step) => $step->id)->values()->all(),
            'steps' => $this->steps->map(function (RoutineStep $step) {
                $check = $this->check($step);

                return [
                    'id' => $step->id,
                    'label' => $step->label,
                    'picture' => $step->picture?->toPicker(),
                    'minutes' => $step->minutes,
                    // A visual timer the child starts, for the step's minutes.
                    'timerMinutes' => $step->timer ? $step->minutes : null,
                    'type' => $step->type->value,
                    'optional' => $step->optional,
                    'done' => $check !== null && ! $check->skipped,
                    'skipped' => $check !== null && $check->skipped,
                ];
            })->values()->all(),
        ];
    }

    /**
     * Part of the day for a routine without one: from "klar senast" or the start.
     */
    public static function dayPartOf(Routine $routine, Family $family): ?DayPart
    {
        $time = Routine::short($routine->start_time) ?? Routine::short($routine->done_by);

        return $routine->day_part ?? ($time === null ? null : $family->dayPartFor($time));
    }
}
