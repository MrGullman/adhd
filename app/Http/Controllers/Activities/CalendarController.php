<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Imorgon and Veckan: only activities, events and what is different. No
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

        return Inertia::render('activities/Tomorrow', [
            'date' => $tomorrow->toDateString(),
            'activities' => $calendar->on($tomorrow)->map(fn (Activity $activity) => $calendar->present($activity, $tomorrow))->values(),
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

        return [
            'offset' => $offset,
            'weekNumber' => $monday->isoWeek(),
            'days' => array_map(function (CarbonImmutable $day) use ($calendar, $child, $today) {
                $activities = $calendar->on($day, $child);
                $shown = $child === null ? $activities : $activities->take(self::CHILD_WEEK_LIMIT);

                return [
                    'date' => $day->toDateString(),
                    'isToday' => $day->isSameDay($today),
                    'isPast' => $day->lt($today),
                    'activities' => $shown->map(fn (Activity $activity) => $calendar->present($activity, $day, $child))->values(),
                    'more' => $activities->count() - $shown->count(),
                ];
            }, $calendar->days()),
        ];
    }
}
