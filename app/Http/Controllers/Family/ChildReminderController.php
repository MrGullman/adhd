<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\ReminderSettingsRequest;
use App\Reminders\TestNotification;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Parents decide a child's reminders and quiet hours, and can send a test
 * to the child's devices.
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

    /**
     * "Skicka en testnotis" to the child's own devices, to see that reminders arrive.
     */
    public function test(CurrentFamily $current, TestNotification $test, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);
        // "Alvas", but "Elias" and "Max".
        $whose = preg_match('/[sxz]$/i', $child->name) === 1 ? $child->name : $child->name.'s';
        $test->send($child, route('child.home'), $whose);

        return to_route('children.show', $child->id);
    }
}
