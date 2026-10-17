<?php

namespace App\Http\Controllers\Activities;

use App\Actions\Activities\SaveActivity;
use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tasks\Concerns\TaskFormOptions;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityItem;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    use TaskFormOptions;

    /**
     * What is coming: every-week activities and what happens from today on.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $today = LocalTime::today();

        $activities = Activity::query()
            ->with(['picture', 'participants.picture'])
            ->withCount('items')
            ->where(fn ($query) => $query
                ->whereNotNull('weekday')
                ->orWhereRaw('coalesce(end_date, date) >= ?', [$today]))
            ->orderByRaw('weekday is not null')
            ->orderBy('date')
            ->orderBy('weekday')
            ->orderBy('time')
            ->get();

        return Inertia::render('activities/Index', [
            'activities' => $activities->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'typeLabel' => $activity->type->label(),
                'name' => $activity->name,
                'picture' => $activity->picture?->toPicker(),
                'when' => $this->describe($activity),
                'weekly' => $activity->isWeekly(),
                'unusual' => $activity->unusual,
                'visibleToChildren' => $activity->visible_to_children,
                'itemCount' => $activity->getAttribute('items_count'),
                'participants' => $activity->participants->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatarColor' => $member->avatar_color->value,
                    'avatarUrl' => $member->avatarUrl(),
                ])->values(),
            ]),
            'canCreate' => $user->can('create', Activity::class),
        ]);
    }

    public function create(Request $request, CurrentFamily $current): Response
    {
        Gate::authorize('create', Activity::class);

        $type = ActivityType::tryFrom($request->string('typ')->toString()) ?? ActivityType::Activity;

        return Inertia::render('activities/Create', [
            ...$this->formOptions($current),
            'type' => $type->value,
            'today' => LocalTime::today(),
        ]);
    }

    public function store(StoreActivityRequest $request, SaveActivity $save): RedirectResponse
    {
        $activity = new Activity;
        $activity->created_by = $request->user()?->id;

        $save->handle($activity, $request->activity(), $request->participants(), $request->items());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$activity->name} är tillagd."]);

        return to_route('activities.index');
    }

    public function edit(CurrentFamily $current, Activity $activity): Response
    {
        Gate::authorize('update', $activity);

        $activity->load(['picture', 'participants', 'items.picture']);

        return Inertia::render('activities/Edit', [
            ...$this->formOptions($current),
            'today' => LocalTime::today(),
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type->value,
                'name' => $activity->name,
                'picture' => $activity->picture?->toPicker(),
                'participants' => $activity->participants->modelKeys(),
                'repeat' => $activity->isWeekly() ? 'weekly' : 'once',
                'date' => $activity->date?->toDateString(),
                'endDate' => $activity->end_date?->toDateString(),
                'weekday' => $activity->weekday,
                'time' => $activity->shortTime(),
                'reminders' => $activity->reminders ?? [],
                'visibleToChildren' => $activity->visible_to_children,
                'unusual' => $activity->unusual,
                'items' => $activity->items->map(fn (ActivityItem $item) => [
                    'id' => $item->id,
                    'label' => $item->label,
                    'picture' => $item->picture?->toPicker(),
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateActivityRequest $request, SaveActivity $save, Activity $activity): RedirectResponse
    {
        $save->handle($activity, $request->activity(), $request->participants(), $request->items());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$activity->name} är sparad."]);

        return to_route('activities.index');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        Gate::authorize('delete', $activity);

        $activity->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$activity->name} är borttagen."]);

        return to_route('activities.index');
    }

    /**
     * "Varje tisdag 17:15", "onsdag 14 oktober 17:15" or "13–15 oktober".
     */
    private function describe(Activity $activity): string
    {
        $time = $activity->shortTime();

        if ($activity->isWeekly()) {
            $day = mb_strtolower($this->weekdayOptions()[$activity->weekday - 1]['label']);

            return trim("Varje {$day} {$time}");
        }

        // The app's locale is Swedish, so Carbon writes the days in Swedish.
        $from = $activity->date;
        $to = $activity->end_date;

        if ($from === null) {
            return '';
        }

        if ($to !== null && ! $to->isSameDay($from)) {
            return $from->translatedFormat('j M').' – '.$to->translatedFormat('j M');
        }

        return trim($from->translatedFormat('l j F').' '.$time);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(CurrentFamily $current): array
    {
        return [
            'members' => $this->taskFormOptions($current->getOrFail())['members'],
            'weekdays' => $this->weekdayOptions(),
            'types' => collect(ActivityType::cases())->map(fn (ActivityType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ];
    }
}
