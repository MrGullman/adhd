<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Calendars\StoreCalendarRequest;
use App\Http\Requests\Calendars\UpdateCalendarRequest;
use App\Jobs\SyncCalendarJob;
use App\Models\Calendar;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Parents add the family's calendars from Google, iPhone or Outlook as iCal
 * links. Reading them always happens in the background.
 */
class CalendarController extends Controller
{
    public function index(CurrentFamily $current): Response
    {
        Gate::authorize('viewAny', Calendar::class);

        return Inertia::render('calendars/Index', [
            'calendars' => Calendar::query()
                ->with('owner:id,name')
                ->withCount('events')
                ->orderBy('name')
                ->get()
                ->map(fn (Calendar $calendar) => [
                    'id' => $calendar->id,
                    'name' => $calendar->name,
                    'userId' => $calendar->user_id,
                    'owner' => $calendar->owner?->name,
                    'visibleToChildren' => $calendar->visible_to_children,
                    'events' => $calendar->events_count,
                    'lastSyncedAt' => $calendar->last_synced_at?->toIso8601String(),
                    'failing' => $calendar->last_failed_at !== null,
                ]),
            'members' => $current->getOrFail()->members()->orderBy('name')->get()->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    public function store(StoreCalendarRequest $request, CurrentFamily $current): RedirectResponse
    {
        $calendar = Calendar::query()->create($request->calendar());

        SyncCalendarJob::dispatch($calendar->id, $current->getOrFail()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$calendar->name} är tillagd. Händelserna kommer om en liten stund."]);

        return to_route('calendars.index');
    }

    public function update(UpdateCalendarRequest $request, CurrentFamily $current, Calendar $calendar): RedirectResponse
    {
        $calendar->update($request->changes());

        if ($request->filled('url')) {
            SyncCalendarJob::dispatch($calendar->id, $current->getOrFail()->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalendern är sparad.']);

        return back();
    }

    public function destroy(Calendar $calendar): RedirectResponse
    {
        Gate::authorize('delete', $calendar);

        $calendar->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$calendar->name} är borttagen."]);

        return back();
    }

    /**
     * "Läs in nu", e.g. right after a change in Google.
     */
    public function sync(CurrentFamily $current, Calendar $calendar): RedirectResponse
    {
        Gate::authorize('update', $calendar);

        SyncCalendarJob::dispatch($calendar->id, $current->getOrFail()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kalendern läses in om en liten stund.']);

        return back();
    }
}
