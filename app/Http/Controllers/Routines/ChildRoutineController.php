<?php

namespace App\Http\Controllers\Routines;

use App\Actions\Routines\CheckRoutineStep;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Routine;
use App\Models\RoutineStep;
use App\Models\User;
use App\Support\ActivityCalendar;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The child does a routine: one step at a time (fixed order) or as a
 * checklist (free order).
 */
class ChildRoutineController extends Controller
{
    public function show(Request $request, Routine $routine): Response
    {
        Gate::authorize('doSteps', $routine);

        /** @var User $child */
        $child = $request->user();
        $routine->load('steps.picture');

        $today = LocalTime::now()->startOfDay();
        $calendar = ActivityCalendar::between($today, $today);

        return Inertia::render('child/Routine', [
            'routine' => RoutineDay::for($routine, $child)->focus(LocalTime::now()),
            // For the step "Kolla packningen": what to have with you today.
            'packing' => $calendar->on($today, $child)
                ->filter(fn (Activity $activity) => $calendar->packs($activity, $child))
                ->map(fn (Activity $activity) => $calendar->present($activity, $today, $child))
                ->values(),
        ]);
    }

    public function check(Request $request, CheckRoutineStep $check, Routine $routine, RoutineStep $step): RedirectResponse
    {
        Gate::authorize('doSteps', $routine);

        /** @var User $child */
        $child = $request->user();
        $check->check($routine, $child, $step);

        return back();
    }

    /**
     * "Vi hoppar över det idag": an optional step when time is short.
     */
    public function skip(Request $request, CheckRoutineStep $check, Routine $routine, RoutineStep $step): RedirectResponse
    {
        Gate::authorize('doSteps', $routine);
        abort_unless($step->optional, 403);

        /** @var User $child */
        $child = $request->user();
        $check->check($routine, $child, $step, skipped: true);

        return back();
    }

    public function uncheck(Request $request, CheckRoutineStep $check, Routine $routine, RoutineStep $step): RedirectResponse
    {
        Gate::authorize('doSteps', $routine);

        /** @var User $child */
        $child = $request->user();
        $check->uncheck($routine, $child, $step);

        return back();
    }
}
