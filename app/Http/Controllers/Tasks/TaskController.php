<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tasks\Concerns\TaskFormOptions;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Support\CurrentFamily;
use App\Support\DayPlan;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    use TaskFormOptions;

    /**
     * One day's tasks, grouped by part of the day and sorted by time.
     */
    public function index(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $date = $this->dateFrom($request->query('dag'));

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('tasks/Index', [
            'date' => $date->toDateString(),
            'today' => LocalTime::today(),
            'previousDate' => $date->subDay()->toDateString(),
            'nextDate' => $date->addDay()->toDateString(),
            'groups' => DayPlan::for($family, $date->toDateString())->groups(),
            'canCreate' => $user->can('create', Task::class),
        ]);
    }

    public function create(Request $request, CurrentFamily $current): Response
    {
        Gate::authorize('create', Task::class);

        $family = $current->getOrFail();

        return Inertia::render('tasks/Create', [
            ...$this->taskFormOptions($family),
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
            ...$this->taskFormOptions($current->getOrFail()),
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'picture' => $task->picture?->toPicker(),
                'assignedTo' => $task->assigned_to,
                'date' => $task->date->toDateString(),
                'dayPart' => $task->time === null ? $task->day_part?->value : null,
                'time' => $task->shortTime(),
                'timerMinutes' => $task->timer_minutes,
                'recurring' => $task->isRecurring(),
            ],
        ]);
    }

    public function update(UpdateTaskRequest $request, CurrentFamily $current, Task $task): RedirectResponse
    {
        $task->fill($request->task($current->getOrFail()));
        // A day of a recurring task changed by hand keeps its changes.
        $task->customized = $task->isRecurring();
        $task->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är sparad."]);

        return to_route('tasks.index', ['dag' => $task->date->toDateString()]);
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        if ($task->isRecurring()) {
            // Kept as a skipped day, so that it is not created again.
            $task->delete();
            $message = "{$task->title} hoppas över den här dagen.";
        } else {
            $task->forceDelete();
            $message = "{$task->title} är borttagen.";
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

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
}
