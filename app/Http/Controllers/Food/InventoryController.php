<?php

namespace App\Http\Controllers\Food;

use App\Enums\StorageLocation;
use App\Food\Inventory;
use App\Food\Units;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\UpdatePantryAmountRequest;
use App\Models\PantryItem;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inventera: go through one place at home (kylskåp, frys, skafferi, annat),
 * change what is left with + and −, and mark what is finished.
 */
class InventoryController extends Controller
{
    public function show(CurrentFamily $current, string $location): Response
    {
        Gate::authorize('create', PantryItem::class);
        $place = StorageLocation::tryFrom($location) ?? abort(404);

        return Inertia::render('food/Inventory', [
            'location' => ['value' => $place->value, 'label' => $place->label()],
            'locations' => array_map(fn (StorageLocation $other) => ['value' => $other->value, 'label' => $other->label()], StorageLocation::cases()),
            'inventoriedAt' => Inventory::lastTaken($current->getOrFail())[$place->value] ?? null,
            'items' => PantryItem::query()
                ->with('ingredient.picture')
                ->where('location', $place)
                ->get()
                ->filter(fn (PantryItem $item) => $item->ingredient !== null)
                ->sortBy(fn (PantryItem $item) => $item->ingredient?->name)
                ->map(fn (PantryItem $item) => PantryController::present($item))
                ->values(),
            'units' => Units::all(),
        ]);
    }

    /**
     * How much is left (or where it is kept). Nothing left means it is gone.
     */
    public function update(UpdatePantryAmountRequest $request, PantryItem $pantryItem): RedirectResponse
    {
        if ($request->has('quantity') && $request->filled('quantity') && (float) $request->input('quantity') <= 0) {
            $pantryItem->delete();

            return back();
        }

        $pantryItem->update($request->safe()->only(['quantity', 'unit', 'location']));

        return back();
    }

    /**
     * "Klar med kylskåpet": the place has been gone through today.
     */
    public function done(CurrentFamily $current, string $location): RedirectResponse
    {
        Gate::authorize('create', PantryItem::class);
        $place = StorageLocation::tryFrom($location) ?? abort(404);

        Inventory::markTaken($current->getOrFail(), $place);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$place->label()} är inventerat."]);

        return to_route('pantry.index');
    }
}
