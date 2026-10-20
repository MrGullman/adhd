<?php

namespace App\Calendar;

use App\Display\DisplayContext;
use App\Enums\FamilyRole;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\Membership;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * The family calendars' events for a period, read from the database only.
 *
 * Who sees what ("Visa för", calendar_child):
 * - parents see every calendar
 * - a child sees the calendars shown to that child
 * - other adults and the home display see the calendars shown to any child
 *
 * An event named like an activity (e.g. "Simskola") gets the activity's
 * picture, and is left out on days the activity is already shown.
 */
class FamilyCalendar
{
    /** @var Collection<int, CalendarEvent> */
    private Collection $events;

    /** @var array<int, FamilyRole|null> Roles by user id, asked once per viewer. */
    private array $roles = [];

    /** @var array<string, Activity> Activities by lower-case name. */
    private array $activities = [];

    public function __construct(CarbonImmutable $from, CarbonImmutable $to)
    {
        // Events can start before the period and last into it.
        $start = $from->startOfDay()->subWeek()->utc();
        $end = $to->endOfDay()->utc();

        $this->events = CalendarEvent::query()
            ->with(['calendar.owner.picture', 'calendar.children:id'])
            ->where('starts_at', '<=', $end)
            ->where(fn ($query) => $query->where('ends_at', '>=', $start)->orWhere(fn ($query) => $query->whereNull('ends_at')->where('starts_at', '>=', $start)))
            ->orderBy('all_day', 'desc')
            ->orderBy('starts_at')
            ->get();

        foreach (Activity::query()->with('picture')->get() as $activity) {
            $this->activities[mb_strtolower(trim($activity->name))] ??= $activity;
        }
    }

    public static function between(CarbonImmutable $from, CarbonImmutable $to): self
    {
        return new self($from, $to);
    }

    /**
     * Events on a day as someone sees them: a family member, "display" for
     * the home display, or null for every event (e.g. reminders).
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
            return $calendar->isShownToChildren();
        }

        if (! $viewer instanceof User) {
            return false;
        }

        return match ($this->roleOf($viewer)) {
            FamilyRole::Parent => true,
            FamilyRole::Child => $calendar->isShownTo($viewer),
            // Other adults never see the parents' own calendars.
            FamilyRole::Editor, FamilyRole::Viewer => $calendar->isShownToChildren(),
            null => false,
        };
    }

    private function roleOf(User $user): ?FamilyRole
    {
        $current = app(CurrentFamily::class);

        if ($user->is(Auth::user())) {
            return $current->role();
        }

        if (! array_key_exists($user->id, $this->roles)) {
            $membership = $current->getOrFail()->members()->whereKey($user->id)->first()?->getRelation('pivot');
            $this->roles[$user->id] = $membership instanceof Membership ? $membership->role : null;
        }

        return $this->roles[$user->id];
    }
}
