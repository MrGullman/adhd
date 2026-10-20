<?php

namespace App\Http\Controllers\Routines;

use App\Actions\Routines\SaveRoutine;
use App\Enums\DayPart;
use App\Enums\Weeks;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tasks\Concerns\TaskFormOptions;
use App\Http\Requests\Routines\StoreRoutineRequest;
use App\Http\Requests\Routines\UpdateRoutineRequest;
use App\Models\Family;
use App\Models\Routine;
use App\Models\RoutineStep;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use App\Support\RoutineDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoutineController extends Controller
{
    use TaskFormOptions;

    public function index(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $now = LocalTime::now();

        /** @var User $user */
        $user = $request->user();

        $routines = Routine::query()
            ->with(['picture', 'children.picture', 'steps.picture'])
            ->orderBy('start_time')
            ->orderBy('done_by')
            ->orderBy('name')
            ->get();

        return Inertia::render('routines/Index', [
            'routines' => $routines->map(fn (Routine $routine) => [
                'id' => $routine->id,
                'name' => $routine->name,
                'picture' => $routine->picture?->toPicker(),
                'schedule' => $this->describe($routine),
                'stepCount' => $routine->steps->count(),
                'today' => $routine->runsOn($now)
                    ? $routine->children->map(fn (User $child) => RoutineDay::for($routine, $child, $now->startOfDay())->summary($family, $now))->values()
                    : [],
            ]),
            'templates' => $user->can('create', Routine::class)
                ? Routine::templates()->withCount('steps')->orderBy('id')->get()->map(fn (Routine $template) => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'stepCount' => $template->getAttribute('steps_count'),
                ])
                : [],
            'canCreate' => $user->can('create', Routine::class),
        ]);
    }

    public function create(CurrentFamily $current): Response
    {
        Gate::authorize('create', Routine::class);

        return Inertia::render('routines/Create', $this->formOptions($current->getOrFail()));
    }

    public function store(StoreRoutineRequest $request, CurrentFamily $current, SaveRoutine $save): RedirectResponse
    {
        $routine = new Routine;
        $routine->family_id = $current->getOrFail()->id;
        $routine->created_by = $request->user()?->id;

        $save->handle($routine, $request->routine(), $request->children(), $request->steps());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$routine->name} är skapad."]);

        return to_route('routines.index');
    }

    public function edit(CurrentFamily $current, Routine $routine): Response
    {
        Gate::authorize('update', $routine);

        $routine->load(['picture', 'children', 'steps.picture']);

        return Inertia::render('routines/Edit', [
            ...$this->formOptions($current->getOrFail()),
            'routine' => [
                'id' => $routine->id,
                'name' => $routine->name,
                'picture' => $routine->picture?->toPicker(),
                'children' => $routine->children->pluck('id')->values(),
                'dayPart' => $routine->day_part?->value,
                'startTime' => Routine::short($routine->start_time),
                'doneBy' => Routine::short($routine->done_by),
                'weekdays' => $routine->weekdays ?? [],
                'weeks' => $routine->weeks->value,
                'startsOn' => $routine->starts_on?->toDateString(),
                'endsOn' => $routine->ends_on?->toDateString(),
                'ordered' => $routine->ordered,
                'steps' => $routine->steps->map(fn (RoutineStep $step) => [
                    'id' => $step->id,
                    'label' => $step->label,
                    'picture' => $step->picture?->toPicker(),
                    'minutes' => $step->minutes,
                    'timer' => $step->timer,
                    'type' => $step->type->value,
                    'optional' => $step->optional,
                    'weekdays' => $step->weekdays ?? [],
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateRoutineRequest $request, SaveRoutine $save, Routine $routine): RedirectResponse
    {
        $save->handle($routine, $request->routine(), $request->children(), $request->steps());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$routine->name} är sparad."]);

        return to_route('routines.index');
    }

    public function destroy(Routine $routine): RedirectResponse
    {
        Gate::authorize('delete', $routine);

        $routine->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$routine->name} är borttagen."]);

        return to_route('routines.index');
    }

    /**
     * "Varje dag · klar 07:45" or "Mån, ons · 15:30–16:30".
     */
    private function describe(Routine $routine): string
    {
        $names = array_column($this->weekdayOptions(), 'short', 'value');
        $days = $routine->weekdays === null
            ? 'Varje dag'
            : implode(', ', array_map(fn (int $day) => $names[$day], $routine->weekdays));

        $start = Routine::short($routine->start_time);
        $doneBy = Routine::short($routine->done_by);

        $when = match (true) {
            $start !== null && $doneBy !== null => "{$start}–{$doneBy}",
            $doneBy !== null => "klar {$doneBy}",
            $start !== null => "från {$start}",
            default => $routine->day_part === null ? null : mb_strtolower($routine->day_part->label()),
        };

        if ($routine->weeks !== Weeks::All) {
            $days .= ' · '.mb_strtolower($routine->weeks->label());
        }

        $period = match (true) {
            $routine->starts_on !== null && $routine->ends_on !== null => $routine->starts_on->format('j/n').'–'.$routine->ends_on->format('j/n'),
            $routine->starts_on !== null => 'från '.$routine->starts_on->format('j/n'),
            $routine->ends_on !== null => 'till '.$routine->ends_on->format('j/n'),
            default => null,
        };

        if ($period !== null) {
            $days .= " · {$period}";
        }

        return $when === null ? $days : "{$days} · {$when}";
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Family $family): array
    {
        return [
            'children' => $family->children()
                ->with('picture')
                ->orderBy('name')
                ->get()
                ->map(fn (User $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'avatarColor' => $child->avatar_color->value,
                    'avatarUrl' => $child->avatarUrl(),
                ]),
            'dayParts' => collect(DayPart::cases())->map(fn (DayPart $part) => [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
            ]),
            'weekdays' => $this->weekdayOptions(),
            'weekOptions' => collect(Weeks::cases())->map(fn (Weeks $weeks) => ['value' => $weeks->value, 'label' => $weeks->label()]),
            'currentWeek' => (int) LocalTime::now()->format('W'),
        ];
    }
}
