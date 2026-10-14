<?php

namespace App\Http\Controllers\Activities;

use App\Calendar\FamilyCalendar;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Imorgon and Veckan: activities, calendar events and what is different. No
 * routines or everyday tasks (docs/ARCHITECTURE.md).
 */
class CalendarController extends Controller
{
    /**
     * At most this many things per day on a child's week.
     */
    private const CHILD_WEEK_LIMIT = 3;

    public function tomorrow(): Response
    {
        $tomorrow = LocalTime::now()->startOfDay()->addDay();
        $calendar = ActivityCalendar::between($tomorrow, $tomorrow);
        $activities = $calendar->on($tomorrow);
        $events = FamilyCalendar::between($tomorrow, $tomorrow);

        return Inertia::render('activities/Tomorrow', [
            'date' => $tomorrow->toDateString(),
            'activities' => $activities->map(fn (Activity $activity) => $calendar->present($activity, $tomorrow))->values(),
            'calendarEvents' => $events->on($tomorrow, null, $activities->map(fn (Activity $activity) => $activity->name)->values()->all())
                ->map(fn (CalendarEvent $event) => $events->present($event, $tomorrow))
                ->values(),
        ]);
    }

    public function week(Request $request): Response
    {
        return Inertia::render('activities/Week', $this->weekProps($request));
    }

    /**
     * The child's own week, with pictures and at most three things a day.
     */
    public function childWeek(Request $request): Response
    {
        /** @var User $child */
        $child = $request->user();

        return Inertia::render('child/Week', $this->weekProps($request, $child));
    }

    /**
     * @return array<string, mixed>
     */
    private function weekProps(Request $request, ?User $child = null): array
    {
        $today = LocalTime::now()->startOfDay();
        $offset = max(-4, min(8, $request->integer('vecka')));
        $monday = $today->startOfWeek(CarbonInterface::MONDAY)->addWeeks($offset);
        $calendar = ActivityCalendar::between($monday, $monday->addDays(6));
        $events = FamilyCalendar::between($monday, $monday->addDays(6));

        return [
            'offset' => $offset,
            'weekNumber' => $monday->isoWeek(),
            'days' => array_map(function (CarbonImmutable $day) use ($calendar, $events, $child, $today) {
                $activities = $calendar->on($day, $child);
                // Calendar events the viewer may see, not already shown as an activity.
                $dayEvents = $events->on($day, $child, $activities->map(fn (Activity $activity) => $activity->name)->values()->all());

                // A child sees at most three things a day: activities first.
                $shown = $child === null ? $activities : $activities->take(self::CHILD_WEEK_LIMIT);
                $shownEvents = $child === null ? $dayEvents : $dayEvents->take(max(0, self::CHILD_WEEK_LIMIT - $shown->count()));

                return [
                    'date' => $day->toDateString(),
                    'isToday' => $day->isSameDay($today),
                    'isPast' => $day->lt($today),
                    'activities' => $shown->map(fn (Activity $activity) => $calendar->present($activity, $day, $child))->values(),
                    'calendarEvents' => $shownEvents->map(fn (CalendarEvent $event) => $events->present($event, $day))->values(),
                    'more' => $activities->count() + $dayEvents->count() - $shown->count() - $shownEvents->count(),
                ];
            }, $calendar->days()),
        ];
    }
}
