<?php

namespace App\Http\Controllers\Family;

use App\Actions\Family\DisableChildLogin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\ChildPinRequest;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * Turn a child's login off (logs out everywhere) and back on with a new code.
 */
class ChildAccessController extends Controller
{
    public function destroy(CurrentFamily $current, DisableChildLogin $disable, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);
        $disable->handle($child);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Inloggningen för {$child->name} är avstängd. {$child->name} är utloggad överallt.",
        ]);

        return to_route('children.show', $child->id);
    }

    public function store(ChildPinRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $child = $current->getOrFail()->findChildOrFail($child);

        $child->pin = $request->string('pin')->toString();
        $child->failed_pin_attempts = 0;
        $child->login_disabled_at = null;
        $child->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$child->name} kan logga in igen med den nya koden."]);

        return to_route('children.show', $child->id);
    }
}
