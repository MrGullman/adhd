<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityConfirmation;
use App\Models\ActivityItem;
use App\Models\ActivityItemCheck;
use App\Models\User;
use App\Points\Points;
use App\Support\ActivityCalendar;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The child packs on the day ("Har du packat till simskolan?"), one thing at
 * a time or straight away. Tomorrow's packing is only shown, never ticked off
 * ahead: children clicked through it without packing.
 */
class ChildActivityController extends Controller
{
    /**
     * Packa: one thing at a time with a big picture, like a routine, and every thing in a list below.
     */
    public function show(Request $request, Activity $activity, string $date): Response
    {
        $this->authorizeDay($activity, $date);

        /** @var User $child */
        $child = $request->user();
        $day = CarbonImmutable::parse($date, LocalTime::ZONE)->startOfDay();
        $calendar = ActivityCalendar::between($day, $day);
        $loaded = $calendar->on($day, $child)->first(fn (Activity $candidate) => $candidate->is($activity));

        abort_unless($loaded instanceof Activity && $calendar->packsOn($loaded, $child, $day), 404);

        return Inertia::render('child/Pack', [
            'activity' => $calendar->present($loaded, $day, $child),
        ]);
    }

    public function pack(Request $request, Activity $activity, string $date, ActivityItem $item): RedirectResponse
    {
        $this->authorizeDay($activity, $date);

        /** @var User $child */
        $child = $request->user();

        ActivityItemCheck::query()->firstOrCreate(
            ['activity_item_id' => $item->id, 'user_id' => $child->id, 'date' => $date],
            ['activity_id' => $activity->id, 'checked_at' => now()],
        );

        return back();
    }

    public function unpack(Request $request, Activity $activity, string $date, ActivityItem $item): RedirectResponse
    {
        $this->authorizeDay($activity, $date);

        ActivityItemCheck::query()
            ->where('activity_item_id', $item->id)
            ->where('user_id', $request->user()?->id)
            ->whereDate('date', $date)
            ->delete();

        return back();
    }

    /**
     * "Ja, jag har packat", on the day of the activity.
     */
    public function confirm(Request $request, Activity $activity, string $date, Points $points): RedirectResponse
    {
        $this->authorizeDay($activity, $date);

        /** @var User $child */
        $child = $request->user();

        $confirmation = ActivityConfirmation::query()->firstOrCreate(
            ['activity_id' => $activity->id, 'user_id' => $child->id, 'date' => $date],
            ['confirmed_at' => now()],
        );

        $points->award($child, 'packing', $confirmation->id, 'Packade till '.$activity->name);

        return back();
    }

    /**
     * Only a child who goes, only today, and only when the activity happens today.
     */
    private function authorizeDay(Activity $activity, string $date): void
    {
        Gate::authorize('pack', $activity);

        abort_unless(
            $date === LocalTime::today() && $activity->occursOn(CarbonImmutable::parse($date, LocalTime::ZONE)),
            404,
        );
    }
}
