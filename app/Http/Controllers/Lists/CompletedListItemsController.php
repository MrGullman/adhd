<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Models\FamilyList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "Ta bort klara": clear the ticked-off items, e.g. after shopping.
 */
class CompletedListItemsController extends Controller
{
    public function destroy(FamilyList $list): RedirectResponse
    {
        Gate::authorize('update', $list);

        $count = $list->items()->whereNotNull('completed_at')->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count === 1 ? '1 klar sak är borttagen.' : "{$count} klara saker är borttagna.",
        ]);

        return to_route('lists.show', $list);
    }
}
