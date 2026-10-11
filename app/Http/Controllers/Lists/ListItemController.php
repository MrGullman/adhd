<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\StoreListItemRequest;
use App\Http\Requests\Lists\UpdateListItemRequest;
use App\Models\FamilyList;
use App\Models\ListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Items are added from the adults' list page and from the child's shopping
 * page, so every action returns to the page it came from.
 */
class ListItemController extends Controller
{
    public function store(StoreListItemRequest $request, FamilyList $list): RedirectResponse
    {
        $list->items()->create([
            'name' => $request->string('name')->toString(),
            'added_by' => $request->user()?->id,
        ]);

        return back();
    }

    public function update(UpdateListItemRequest $request, FamilyList $list, ListItem $item): RedirectResponse
    {
        $item->update(['name' => $request->string('name')->toString()]);

        return back();
    }

    public function destroy(FamilyList $list, ListItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return back();
    }
}
