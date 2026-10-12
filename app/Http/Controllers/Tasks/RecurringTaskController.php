<?php

namespace App\Http\Controllers\Tasks;

use App\Actions\Tasks\GenerateRecurringTasks;
use App\Enums\Frequency;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Tasks\Concerns\TaskFormOptions;
use App\Http\Requests\Tasks\StoreRecurringTaskRequest;
use App\Http\Requests\Tasks\UpdateRecurringTaskRequest;
use App\Models\Family;
use App\Models\RecurringTask;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTaskController extends Controller
{
    use TaskFormOptions;

    private const WEEKDAYS = [
        1 => 'måndag',
        2 => 'tisdag',
        3 => 'onsdag',
        4 => 'torsdag',
        5 => 'fredag',
        6 => 'lördag',
        7 => 'söndag',
    ];

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $recurringTasks = RecurringTask::query()
            ->with('assignee:id,name,avatar_color')
            ->orderBy('title')
            ->get();

        return Inertia::render('tasks/recurring/Index', [
            'recurringTasks' => $recurringTasks->map(fn (RecurringTask $recurring) => [
                'id' => $recurring->id,
                'title' => $recurring->title,
                'schedule' => $this->describe($recurring),
                'ended' => $recurring->ends_on !== null && $recurring->ends_on->toDateString() < LocalTime::today(),
                'assignee' => $recurring->assignee === null ? null : [
                    'id' => $recurring->assignee->id,
                    'name' => $recurring->assignee->name,
                    'avatarColor' => $recurring->assignee->avatar_color->value,
                ],
            ]),
            'canCreate' => $user->can('create', RecurringTask::class),
        ]);
    }

    public function create(CurrentFamily $current): Response
    {
        Gate::authorize('create', RecurringTask::class);

        return Inertia::render('tasks/recurring/Create', [
            ...$this->formOptions($current->getOrFail()),
            'today' => LocalTime::today(),
        ]);
    }

    public function store(StoreRecurringTaskRequest $request, CurrentFamily $current, GenerateRecurringTasks $generate): RedirectResponse
    {
        $family = $current->getOrFail();

        $recurring = DB::transaction(function () use ($request, $family, $generate): RecurringTask {
            $recurring = RecurringTask::create([
                ...$request->recurringTask($family),
                'created_by' => $request->user()?->id,
            ]);

            $generate->handle($family, $recurring);

            return $recurring;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$recurring->title} är tillagd."]);

        return to_route('recurring-tasks.index');
    }

    public function edit(CurrentFamily $current, RecurringTask $recurringTask): Response
    {
        Gate::authorize('update', $recurringTask);

        return Inertia::render('tasks/recurring/Edit', [
            ...$this->formOptions($current->getOrFail()),
            'today' => LocalTime::today(),
            'recurringTask' => [
                'id' => $recurringTask->id,
                'title' => $recurringTask->title,
                'assignedTo' => $recurringTask->assigned_to,
                'frequency' => $recurringTask->frequency->value,
                'weekdays' => $recurringTask->weekdays ?? [],
                'weekInterval' => $recurringTask->week_interval,
                'startsOn' => $recurringTask->starts_on->toDateString(),
                'endsOn' => $recurringTask->ends_on?->toDateString(),
                'dayPart' => $recurringTask->time === null ? $recurringTask->day_part?->value : null,
                'time' => $recurringTask->shortTime(),
            ],
        ]);
    }

    public function update(UpdateRecurringTaskRequest $request, CurrentFamily $current, GenerateRecurringTasks $generate, RecurringTask $recurringTask): RedirectResponse
    {
        $family = $current->getOrFail();

        DB::transaction(function () use ($request, $family, $generate, $recurringTask): void {
            $recurringTask->update($request->recurringTask($family));
            $generate->refresh($family, $recurringTask);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$recurringTask->title} är sparad. Ändringen gäller från imorgon.",
        ]);

        return to_route('recurring-tasks.index');
    }

    public function destroy(GenerateRecurringTasks $generate, RecurringTask $recurringTask): RedirectResponse
    {
        Gate::authorize('delete', $recurringTask);

        DB::transaction(function () use ($generate, $recurringTask): void {
            $generate->removeComingOccurrences($recurringTask);
            $recurringTask->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$recurringTask->title} kommer inte tillbaka."]);

        return to_route('recurring-tasks.index');
    }

    /**
     * "Varje dag", "Måndag och onsdag" or "Varannan vecka: tisdag", with the time or part of the day.
     */
    private function describe(RecurringTask $recurring): string
    {
        if ($recurring->frequency === Frequency::Daily) {
            $days = 'Varje dag';
        } else {
            $names = array_map(fn (int $day) => self::WEEKDAYS[$day], $recurring->weekdays ?? []);
            $last = array_pop($names);
            $list = $names === [] ? (string) $last : implode(', ', $names).' och '.$last;
            $days = $recurring->week_interval === 2 ? "Varannan vecka: {$list}" : ucfirst($list);
        }

        $when = $recurring->shortTime() ?? $recurring->day_part?->label();

        return $when === null ? $days : "{$days} · ".mb_strtolower($when);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Family $family): array
    {
        return [
            ...$this->taskFormOptions($family),
            'weekdays' => collect(self::WEEKDAYS)->map(fn (string $name, int $day) => [
                'value' => $day,
                'label' => ucfirst($name),
                'short' => ucfirst(mb_substr($name, 0, 3)),
            ])->values(),
        ];
    }
}
