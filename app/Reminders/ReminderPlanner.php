<?php

namespace App\Reminders;

use App\Calendar\FamilyCalendar;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Routine;
use App\Models\Task;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use Carbon\CarbonImmutable;

/**
 * Which reminders are due for the current family (docs/ARCHITECTURE.md,
 * PWA och påminnelser):
 *
 * - a task with a time, at that time, to the one it is for
 * - a routine, when it starts, to each child that has it
 * - a calendar event, 15 minutes before, to whose calendar it is
 * - packing, at 18:00 the day before, to each child that packs
 *
 * Reminders are due for a while after their time, in case a run is late.
 */
class ReminderPlanner
{
    public const CATCH_UP_MINUTES = 10;

    public const EVENT_MINUTES_BEFORE = 15;

    public const PACKING_TIME = '18:00';

    private ?Family $family = null;

    /**
     * @return list<Reminder>
     */
    public function due(Family $family, CarbonImmutable $now): array
    {
        $this->family = $family;
        $now = $now->setTimezone(LocalTime::ZONE);
        $today = $now->startOfDay();
        $from = $now->subMinutes(self::CATCH_UP_MINUTES);

        $reminders = [
            ...$this->tasks($today),
            ...$this->routines($today),
            ...$this->events($today),
            ...$this->packing($family, $today),
        ];

        return array_values(array_filter($reminders, fn (Reminder $reminder) => $reminder->at->gt($from) && $reminder->at->lte($now)));
    }

    /**
     * @return list<Reminder>
     */
    private function tasks(CarbonImmutable $today): array
    {
        return array_values(Task::query()
            ->with('assignee')
            ->whereDate('date', $today->toDateString())
            ->whereNotNull('time')
            ->whereNotNull('assigned_to')
            ->whereNull('completed_at')
            ->get()
            ->filter(fn (Task $task) => $task->assignee !== null)
            ->map(fn (Task $task) => new Reminder(
                $task->assignee,
                "task:{$task->id}",
                $this->at($today, (string) $task->time),
                $task->title,
                'Klockan '.$task->shortTime(),
                $this->home($task->assignee),
            ))
            ->all());
    }

    /**
     * @return list<Reminder>
     */
    private function routines(CarbonImmutable $today): array
    {
        $reminders = [];

        foreach (Routine::query()->with(['children', 'steps'])->whereNotNull('start_time')->get() as $routine) {
            if (! $routine->runsOn($today)) {
                continue;
            }

            foreach ($routine->children as $child) {
                if (RoutineDay::for($routine, $child, $today)->isCompleted()) {
                    continue;
                }

                $reminders[] = new Reminder(
                    $child,
                    "routine:{$routine->id}:{$today->toDateString()}",
                    $this->at($today, (string) $routine->start_time),
                    $routine->name,
                    'Dags att börja',
                    route('child.routines.show', $routine),
                );
            }
        }

        return $reminders;
    }

    /**
     * @return list<Reminder>
     */
    private function events(CarbonImmutable $today): array
    {
        $tomorrow = $today->addDay();
        $calendar = FamilyCalendar::between($today, $tomorrow);
        $reminders = [];

        foreach ([$today, $tomorrow] as $day) {
            foreach ($calendar->on($day) as $event) {
                $owner = $event->calendar?->owner;

                if ($owner === null || $event->all_day || $event->timeOn($day) === null) {
                    continue;
                }

                $reminders[] = new Reminder(
                    $owner,
                    "event:{$event->id}",
                    CarbonImmutable::instance($event->starts_at)->setTimezone(LocalTime::ZONE)->subMinutes(self::EVENT_MINUTES_BEFORE),
                    $event->title,
                    'Klockan '.$event->timeOn($day).($event->location ? ' · '.$event->location : ''),
                    $this->home($owner),
                );
            }
        }

        return $reminders;
    }

    /**
     * "Packa till simskolan": the evening before, to each child that packs
     * and has not packed everything yet.
     *
     * @return list<Reminder>
     */
    private function packing(Family $family, CarbonImmutable $today): array
    {
        $tomorrow = $today->addDay();
        $calendar = ActivityCalendar::between($tomorrow, $tomorrow);
        $reminders = [];

        foreach ($family->children()->get() as $child) {
            foreach ($calendar->on($tomorrow, $child) as $activity) {
                if (! $calendar->packs($activity, $child)) {
                    continue;
                }

                $packing = $calendar->packing($activity, $child, $tomorrow);

                if ($packing['packed'] >= $packing['total']) {
                    continue;
                }

                $reminders[] = new Reminder(
                    $child,
                    "packing:{$activity->id}:{$tomorrow->toDateString()}",
                    $this->at($today, self::PACKING_TIME),
                    'Packa till '.$activity->name,
                    $activity->name.' är imorgon',
                    route('child.home'),
                );
            }
        }

        return $reminders;
    }

    private function at(CarbonImmutable $day, string $time): CarbonImmutable
    {
        return $day->setTimeFromTimeString(substr($time, 0, 5));
    }

    /**
     * Where the reminder opens: Min dag for a child, Idag for an adult.
     */
    private function home(User $user): string
    {
        return $this->family !== null && $user->roleIn($this->family) === FamilyRole::Child
            ? route('child.home')
            : route('dashboard');
    }
}
