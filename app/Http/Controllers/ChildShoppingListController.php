<?php

namespace App\Http\Controllers;

use App\Enums\ListType;
use App\Models\FamilyList;
use App\Models\ListItem;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The child's view of the shopping list: add what is missing and tick off.
 */
class ChildShoppingListController extends Controller
{
    public function __invoke(): Response
    {
        $list = FamilyList::query()->where('type', ListType::Shopping)->firstOrFail();
        Gate::authorize('view', $list);

        $items = $list->items()
            ->with('picture')
            ->orderByRaw('completed_at is not null')
            ->orderByDesc('completed_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('child/ShoppingList', [
            'list' => ['id' => $list->id],
            'items' => $items->map(fn (ListItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'picture' => $item->picture?->toPicker(),
                'completed' => $item->isCompleted(),
            ]),
        ]);
    }
}
