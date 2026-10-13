<?php

namespace App\Calendar;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The family calendars' events for a period, read from the database only.
 *
 * Who sees what:
 * - adults see every calendar
 * - a child sees their own calendar and the ones shown to the children
 * - the home display shows what any child may see
 *
 * An event named like an activity (e.g. "Simskola") gets the activity's
 * picture, and is left out on days the activity is already shown.
 */
class FamilyCalendar
{
    /** @var Collection<int, CalendarEvent> */
    private Collection $events;

    /** @var list<int> */
    private array $childIds;

    /** @var array<string, Activity> Activities by lower-case name. */
    private array $activities = [];

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        // Events can start before the period and last into it.
        $start = $from->startOfDay()->subWeek()->utc();
        $end = $to->endOfDay()->utc();

        $this->events = CalendarEvent::query()
            ->with('calendar.owner.picture')
            ->where('starts_at', '<=', $end)
            ->where(fn ($query) => $query->where('ends_at', '>=', $start)->orWhere(fn ($query) => $query->whereNull('ends_at')->where('starts_at', '>=', $start)))
            ->orderBy('all_day', 'desc')
            ->orderBy('starts_at')
            ->get();

        $this->childIds = array_values(array_map(
            intval(...),
            app(CurrentFamily::class)->getOrFail()->children()->pluck('users.id')->all(),
        ));

        foreach (Activity::query()->with('picture')->get() as $activity) {
            $this->activities[mb_strtolower(trim($activity->name))] ??= $activity;
        }
    }

    public static function between(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($from, $to);
    }

    /**
     * Events on a day as someone sees them: null for an adult, a child, or
     * "display" for the home display.
     *
     * @param  array<int, string>  $activityNames  Activities shown the same day, to not show twice.
     * @return Collection<int, CalendarEvent>
     */
    public function on(CarbonImmutable $day, User|string|null $viewer = null, array $activityNames = []): Collection
    {
        $shown = array_map(fn (string $name) => mb_strtolower(trim($name)), $activityNames);

        return $this->events
            ->filter(fn (CalendarEvent $event) => $event->occursOn($day)
                && $this->visibleTo($event, $viewer)
                && ! in_array(mb_strtolower(trim($event->title)), $shown, true))
            ->values();
    }

    /**
     * @return array{id: int, title: string, time: string|null, endTime: string|null, allDay: bool, location: string|null, calendar: string|null, owner: array{id: int, name: string, avatarColor: string, avatarUrl: string|null}|null, picture: array<string, mixed>|null}
     */
    public function present(CalendarEvent $event, CarbonImmutable $day): array
    {
        $owner = $event->calendar?->owner;
        $activity = $this->activities[mb_strtolower(trim($event->title))] ?? null;
        $endsToday = $event->ends_at !== null && $event->ends_at->copy()->setTimezone(LocalTime::ZONE)->toDateString() === $day->toDateString();

        return [
            'id' => $event->id,
            'title' => $event->title,
            'time' => $event->timeOn($day),
            // Also "→ 07:20" on the morning an overnight event ends.
            'endTime' => $endsToday ? $event->endTime() : null,
            'allDay' => $event->all_day,
            'location' => $event->location,
            'calendar' => $event->calendar?->name,
            'owner' => $owner === null ? null : DisplayContext::member($owner),
            'picture' => $activity?->picture?->toPicker(),
        ];
    }

    /**
     * Whose row the event belongs to in the week grid: the calendar's owner.
     */
    public function ownerId(CalendarEvent $event): ?int
    {
        return $event->calendar?->user_id;
    }

    private function visibleTo(CalendarEvent $event, User|string|null $viewer): bool
    {
        $calendar = $event->calendar;

        if ($calendar === null) {
            return false;
        }

        if ($viewer === null) {
            return true;
        }

        if ($viewer === 'display') {
            return $calendar->isVisibleToChildren($this->childIds);
        }

        if ($viewer instanceof User) {
            // A child's own calendar, or one shown to the children that is not a sibling's.
            return $calendar->user_id === $viewer->id
                || ($calendar->visible_to_children && ! in_array($calendar->user_id, $this->childIds, true));
        }

        return false;
    }
}
