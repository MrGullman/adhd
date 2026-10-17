<?php

namespace App\Support;

use App\Enums\DayPart;
use App\Models\Family;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * One day's tasks for a family, grouped by part of the day and sorted by
 * time. Used by Uppgifter, the adults' Idag and the child's Min dag.
 */
class DayPlan
{
    /**
     * @param  Collection<int, Task>  $tasks
     */
    private function __construct(private Family $family, private Collection $tasks) {}

    /**
     * The day's tasks, optionally only one member's.
     */
    public static function for(Family $family, string $date, ?int $assignedTo = null): self
    {
        $tasks = Task::query()
            ->with(['picture', 'assignee:id,name,avatar_color,picture_id', 'assignee.picture'])
            ->whereDate('date', $date)
            ->when($assignedTo !== null, fn ($query) => $query->where('assigned_to', $assignedTo))
            ->orderByRaw('time is null')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        return new self($family, $tasks);
    }

    /**
     * One group per part of the day, then "Under dagen" for tasks with neither.
     *
     * @return list<array{value: string|null, label: string, start: string|null, tasks: list<array<string, mixed>>}>
     */
    public function groups(): array
    {
        $groups = [];

        foreach (DayPart::cases() as $part) {
            $groups[] = [
                'value' => $part->value,
                'label' => $part->label(),
                'start' => $this->family->dayParts()[$part->value],
                'tasks' => $this->present($this->tasks->filter(fn (Task $task) => $task->day_part === $part)),
            ];
        }

        $groups[] = [
            'value' => null,
            'label' => 'Under dagen',
            'start' => null,
            'tasks' => $this->present($this->tasks->filter(fn (Task $task) => $task->day_part === null)),
        ];

        return $groups;
    }

    /**
     * What to do now: the first task not done in the current part of the day
     * or later. Earlier parts are not pushed again, and tasks "Under dagen"
     * come last.
     *
     * @return array<string, mixed>|null
     */
    public function now(DayPart $current): ?array
    {
        $order = array_flip(array_map(fn (DayPart $part) => $part->value, DayPart::cases()));

        $next = $this->tasks
            ->reject(fn (Task $task) => $task->isCompleted())
            ->filter(fn (Task $task) => $task->day_part === null || $order[$task->day_part->value] >= $order[$current->value])
            ->sortBy(fn (Task $task) => $task->day_part === null ? 99 : $order[$task->day_part->value], SORT_NUMERIC)
            ->first();

        return $next === null ? null : $this->presentTask($next);
    }

    /**
     * @return array{total: int, completed: int}
     */
    public function progress(): array
    {
        return [
            'total' => $this->tasks->count(),
            'completed' => $this->tasks->filter(fn (Task $task) => $task->isCompleted())->count(),
        ];
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return list<array<string, mixed>>
     */
    private function present(Collection $tasks): array
    {
        return array_values($tasks->map(fn (Task $task) => $this->presentTask($task))->all());
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'picture' => $task->picture?->toPicker(),
            'time' => $task->shortTime(),
            'timerMinutes' => $task->timer_minutes,
            'dayPart' => $task->day_part?->value,
            'completed' => $task->isCompleted(),
            'recurring' => $task->isRecurring(),
            'ownTask' => $task->isOwnTask(),
            'assignee' => $task->assignee === null ? null : [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'avatarColor' => $task->assignee->avatar_color->value,
                'avatarUrl' => $task->assignee->avatarUrl(),
            ],
        ];
    }
}
