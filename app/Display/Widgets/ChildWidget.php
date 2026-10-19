<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;
use App\Models\Membership;
use App\Models\Routine;
use App\Models\RoutineStep;
use App\Models\User;
use App\Support\DayPlan;
use App\Support\RoutineDay;
use App\Support\SchoolLunch;

/**
 * Barnets dag: what one child does now (a routine step with quarter dots, or
 * the next task), what comes after, and today's and tomorrow's activities.
 */
class ChildWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'child';
    }

    public function label(): string
    {
        return 'Barnets dag';
    }

    public function defaultSize(): array
    {
        return [4, 4];
    }

    public function setting(): ?string
    {
        return 'child';
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $child = $context->children()->first(fn (User $user) => $user->id === (int) $widget->setting('child_id'));

        if ($child === null) {
            return ['child' => null];
        }

        return [
            'child' => DisplayContext::member($child),
            'routine' => $this->routine($context, $child),
            'task' => $this->task($context, $child),
            'activities' => $this->activities($context, $child),
            'lunch' => (new SchoolLunch($context->family, $context->now))->forMenu(
                $child->getRelation('pivot') instanceof Membership ? $child->getRelation('pivot')->school_menu_id : null,
            ),
        ];
    }

    /**
     * The routine the child is doing or it is time for, if any.
     *
     * @return array<string, mixed>|null
     */
    public function routine(DisplayContext $context, User $child): ?array
    {
        $routines = Routine::query()
            ->with(['steps.picture'])
            ->whereHas('children', fn ($query) => $query->whereKey($child->id))
            ->orderBy('start_time')
            ->orderBy('done_by')
            ->get()
            ->filter(fn (Routine $routine) => $routine->runsOn($context->today));

        $days = $routines->map(fn (Routine $routine) => RoutineDay::for($routine, $child, $context->today));

        /** @var RoutineDay|null $day */
        $day = $days->first(fn (RoutineDay $day) => ! $day->isCompleted() && ($day->run !== null || $day->isDue($context->family, $context->now)));

        if ($day === null) {
            $done = $days->first(fn (RoutineDay $day) => $day->isCompleted() && $day->run?->completed_at?->gt(now()->subHour()));

            return $done === null ? null : ['name' => $done->routine->name, 'completed' => true];
        }

        $current = $day->currentStep();
        $progress = $day->progress();
        $next = $current === null ? null : $day->steps()
            ->skipUntil(fn (RoutineStep $step) => $step->is($current))
            ->skip(1)
            ->first(fn (RoutineStep $step) => $day->check($step) === null);

        return [
            'name' => $day->routine->name,
            'completed' => false,
            'step' => $current === null ? null : [
                'label' => $current->label,
                'picture' => $current->picture?->toPicker(),
                'number' => $progress['completed'] + 1,
                'total' => $progress['total'],
            ],
            'next' => $next?->label,
            'doneBy' => Routine::short($day->routine->done_by),
            'minutesLeft' => $day->minutesLeft($context->now),
            'minutesNeeded' => $day->minutesNeeded(),
        ];
    }

    /**
     * The child's next task now, when no routine is going on.
     *
     * @return array<string, mixed>|null
     */
    private function task(DisplayContext $context, User $child): ?array
    {
        return DayPlan::for($context->family, $context->today->toDateString(), $child->id)->now($context->dayPart());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function activities(DisplayContext $context, User $child): array
    {
        $calendar = $context->calendar();
        $result = [];

        $shown = [];

        foreach (['idag' => $context->today, 'imorgon' => $context->today->addDay()] as $when => $day) {
            foreach ($calendar->on($day, $child) as $activity) {
                // Something lasting several days, e.g. a trip, is shown once.
                if (in_array($activity->id, $shown, true)) {
                    continue;
                }

                $shown[] = $activity->id;
                $packing = $calendar->packsOn($activity, $child, $day) ? $calendar->packing($activity, $child, $day) : null;

                $result[] = [
                    'id' => $activity->id,
                    'when' => $when,
                    'name' => $activity->name,
                    'time' => $activity->shortTime(),
                    'picture' => $activity->picture?->toPicker(),
                    'cancelled' => $calendar->isCancelled($activity, $day),
                    'packed' => $packing === null
                        ? null
                        : $packing['confirmed'] || ($packing['total'] > 0 && $packing['packed'] === $packing['total']),
                ];
            }
        }

        return array_slice($result, 0, 3);
    }
}
