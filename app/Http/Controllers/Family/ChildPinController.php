<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Requests\Family\ChildPinRequest;
use App\Support\ChildPins;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ChildPinController extends Controller
{
    /**
     * A parent chooses a new code.
     */
    public function update(ChildPinRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $child = $current->getOrFail()->findChildOrFail($child);

        $child->pin = $request->string('pin')->toString();
        $child->failed_pin_attempts = 0;
        $child->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Koden är bytt.']);

        return to_route('children.show', $child->id);
    }

    /**
     * The child has forgotten the code: pick a new random one for the parent to tell.
     */
    public function store(CurrentFamily $current, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);

        $child->pin = ChildPins::generate();
        $child->failed_pin_attempts = 0;
        $child->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$child->name} har fått en ny kod."]);

        return to_route('children.show', $child->id);
    }
}
