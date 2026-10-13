<?php

namespace App\Http\Controllers\Lists;

use App\Actions\Meals\MoveToPantry;
use App\Http\Controllers\Controller;
use App\Models\FamilyList;
use App\Models\ListItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Tick an item off, or back on when it was ticked off by mistake.
 */
class ListItemCompletionController extends Controller
{
    public function store(Request $request, MoveToPantry $moveToPantry, FamilyList $list, ListItem $item): RedirectResponse
    {
        Gate::authorize('check', $item);

        /** @var User $user */
        $user = $request->user();

        if (! $item->isCompleted()) {
            $item->complete($user);

            // What is bought ends up in the pantry.
            if ($list->isShopping()) {
                $moveToPantry->handle($item);
            }
        }

        return back();
    }

    public function destroy(FamilyList $list, ListItem $item): RedirectResponse
    {
        Gate::authorize('check', $item);

        $item->reopen();

        return back();
    }
}
