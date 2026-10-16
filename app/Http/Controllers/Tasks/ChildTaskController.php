<?php

namespace App\Http\Controllers\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreOwnTaskRequest;
use App\Models\Task;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A child in läget Ungdom adds their own tasks, e.g. "Plugga till provet".
 * Adults see them in Idag and can change and remove them like other tasks.
 */
class ChildTaskController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('createOwn', Task::class);

        return Inertia::render('child/NewTask', [
            'today' => LocalTime::today(),
            'tomorrow' => LocalTime::now()->addDay()->toDateString(),
        ]);
    }

    public function store(StoreOwnTaskRequest $request, CurrentFamily $current): RedirectResponse
    {
        $task = Task::create($request->task($current->getOrFail()));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är tillagd."]);

        return to_route('child.home');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('deleteOwn', $task);

        $task->forceDelete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$task->title} är borttagen."]);

        return back();
    }
}
