<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Points\Points;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Tick a task off, or back on when it was ticked off by mistake.
 */
class TaskCompletionController extends Controller
{
    public function store(Request $request, Task $task, Points $points): RedirectResponse
    {
        Gate::authorize('complete', $task);

        /** @var User $user */
        $user = $request->user();

        if (! $task->isCompleted()) {
            $task->complete($user);
        }

        // The child the task is for, or the child who did it.
        $points->award($task->assignee ?? $user, 'task', $task->id, $task->title);

        return back();
    }

    public function destroy(Task $task, Points $points): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $task->reopen();
        $points->revoke('task', $task->id);

        return back();
    }
}
