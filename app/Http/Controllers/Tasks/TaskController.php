<?php

namespace App\Http\Controllers\Tasks;

use App\Enums\DayPart;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Family;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    /**
     * One day's tasks, grouped by part of the day and sorted by time.
     */
    public function index(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $date = $this->dateFrom($request->query('dag'));

        /** @var User $user */
        $user = $request->user();

        $tasks = Task::query()
            ->with('assignee:id,name,avatar_color')
            ->whereDate('date', $date->toDateString())
            ->orderByRaw('time is null')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        $groups = [];

        foreach (DayPart::cases() as $part) {
            $groups[] = [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
                'tasks' => $this->present($tasks->filter(fn (Task $task) => $task->day_part === $part)),
            ];
        }

        // Tasks without a part of the day or a time.
        $groups[] = [
            'value' => null,
            'label' => 'Under dagen',
            'start' => null,
            'tasks' => $this->present($tasks->filter(fn (Task $task) => $task->day_part === null)),
        ];

        return Inertia::render('tasks/Index', [
            'date' => $date->toDateString(),
            'today' => LocalTime::today(),
            'previousDate' => $date->subDay()->toDateString(),
            'nextDate' => $date->addDay()->toDateString(),
            'groups' => $groups,
            'canCreate' => $user->can('create', Task::class),
        ]);
    }

    public function create(Request $request, CurrentFamily $current): Response
    {
        Gate::authorize('create', Task::class);

        $family = $current->getOrFail();

        return Inertia::render('tasks/Create', [
            ...$this->formOptions($family),
            'date' => $this->dateFrom($request->query('dag'))->toDateString(),
        ]);
    }

    public function store(StoreTaskRequest $request, CurrentFamily $current): RedirectResponse
    {
        $task = Task::create([
            ...$request->task($current->getOrFail()),
            'created_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är tillagd."]);

        return to_route('tasks.index', ['dag' => $task->date->toDateString()]);
    }

    public function edit(CurrentFamily $current, Task $task): Response
    {
        Gate::authorize('update', $task);

        return Inertia::render('tasks/Edit', [
            ...$this->formOptions($current->getOrFail()),
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'assignedTo' => $task->assigned_to,
                'date' => $task->date->toDateString(),
                'dayPart' => $task->time === null ? $task->day_part?->value : null,
                'time' => $task->shortTime(),
            ],
        ]);
    }

    public function update(UpdateTaskRequest $request, CurrentFamily $current, Task $task): RedirectResponse
    {
        $task->update($request->task($current->getOrFail()));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är sparad."]);

        return to_route('tasks.index', ['dag' => $task->date->toDateString()]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är borttagen."]);

        return to_route('tasks.index', ['dag' => $task->date->toDateString()]);
    }

    /**
     * The requested day (YYYY-MM-DD), or today in Sweden.
     */
    private function dateFrom(mixed $value): CarbonImmutable
    {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, LocalTime::ZONE);

            if ($date !== null && $date->toDateString() === $value) {
                return $date;
            }
        }

        return LocalTime::now()->startOfDay();
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function present(Collection $tasks): array
    {
        return $tasks
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'time' => $task->shortTime(),
                'completed' => $task->isCompleted(),
                'assignee' => $task->assignee === null ? null : [
                    'id' => $task->assignee->id,
                    'name' => $task->assignee->name,
                    'avatarColor' => $task->assignee->avatar_color->value,
                ],
            ])
            ->values()
            ->all();
    }

    /**
     * Who a task can be given to and the parts of the day, for the task form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(Family $family): array
    {
        return [
            'members' => $family->members()
                ->orderBy('name')
                ->get(['users.id', 'users.name', 'users.avatar_color'])
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'avatarColor' => $member->avatar_color->value,
                ]),
            'dayParts' => collect(DayPart::cases())->map(fn (DayPart $part) => [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $family->dayParts()[$part->value],
            ]),
        ];
    }
}
