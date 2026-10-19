<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Models\FamilyList;
use App\Models\ListItem;
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

        // One at a time, so that a connected ICA list loses them too (ListItemObserver).
        $done = $list->items()->whereNotNull('completed_at')->get();
        $done->each(fn (ListItem $item) => $item->delete());
        $count = $done->count();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $count === 1 ? '1 klar sak är borttagen.' : "{$count} klara saker är borttagna.",
        ]);

        return to_route('lists.show', $list);
    }
}
