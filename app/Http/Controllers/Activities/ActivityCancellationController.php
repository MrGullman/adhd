<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityCancellation;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "Inställd den här gången": one occurrence of a weekly activity, e.g. no
 * simskola the week the pool is closed. Undone with Ångra.
 */
class ActivityCancellationController extends Controller
{
    public function store(Request $request, Activity $activity, string $date): RedirectResponse
    {
        Gate::authorize('update', $activity);
        $this->ensureOccurrence($activity, $date);

        ActivityCancellation::query()->firstOrCreate(
            ['activity_id' => $activity->id, 'date' => $date],
            ['created_by' => $request->user()?->id],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$activity->name} är inställd ".CarbonImmutable::parse($date)->translatedFormat('l j F').'.']);

        return back();
    }

    public function destroy(Activity $activity, string $date): RedirectResponse
    {
        $cancellation = ActivityCancellation::query()
            ->where('activity_id', $activity->id)
            ->whereDate('date', $date)
            ->firstOrFail();
        Gate::authorize('delete', $cancellation);

        $cancellation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$activity->name} blir av som vanligt."]);

        return back();
    }

    /**
     * Only a weekly activity, on a day it happens, and not one that has been.
     */
    private function ensureOccurrence(Activity $activity, string $date): void
    {
        abort_unless(
            $activity->isWeekly()
                && $date >= LocalTime::today()
                && $activity->occursOn(CarbonImmutable::parse($date, LocalTime::ZONE)),
            404,
        );
    }
}
