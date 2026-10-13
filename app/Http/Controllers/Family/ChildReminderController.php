<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\ReminderSettingsRequest;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Parents decide a child's reminders and quiet hours.
 */
class ChildReminderController extends Controller
{
    public function update(ReminderSettingsRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);
        $child->forceFill($request->settings())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('children.show', $child->id);
    }
}
