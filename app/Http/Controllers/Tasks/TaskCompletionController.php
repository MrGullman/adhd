<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Tick a task off, or back on when it was ticked off by mistake.
 */
class TaskCompletionController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('complete', $task);

        /** @var User $user */
        $user = $request->user();

        if (! $task->isCompleted()) {
            $task->complete($user);
        }

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('complete', $task);

        $task->reopen();

        return back();
    }
}
