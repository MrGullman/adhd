<?php

namespace App\Http\Controllers\Lists;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lists\StoreListItemRequest;
use App\Http\Requests\Lists\UpdateListItemRequest;
use App\Models\FamilyList;
use App\Models\Ingredient;
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
        $name = $request->string('name')->toString();

        $list->items()->create([
            // On the shopping list a known grocery gets its store section.
            'ingredient_id' => $list->isShopping()
                ? Ingredient::query()->whereRaw('lower(name) = ?', [mb_strtolower(trim($name))])->value('id')
                : null,
            'name' => $name,
            'picture_id' => $request->filled('picture_id') ? $request->integer('picture_id') : null,
            'added_by' => $request->user()?->id,
        ]);

        return back();
    }

    public function update(UpdateListItemRequest $request, FamilyList $list, ListItem $item): RedirectResponse
    {
        $item->update([
            'name' => $request->string('name')->toString(),
            'picture_id' => $request->filled('picture_id') ? $request->integer('picture_id') : null,
        ]);

        return back();
    }

    public function destroy(FamilyList $list, ListItem $item): RedirectResponse
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return back();
    }
}
