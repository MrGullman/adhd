<?php

namespace App\Http\Controllers\Food;

use App\Enums\StorageLocation;
use App\Food\IngredientText;
use App\Food\Inventory;
use App\Food\Units;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\StorePantryItemRequest;
use App\Models\Ingredient;
use App\Models\PantryItem;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Skafferi: what is at home, by where it is kept (kylskåp, frys, skafferi,
 * annat). Staples (basvaror) always count as at home.
 */
class PantryController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response
    {
        /** @var User $user */
        $user = $request->user();

        $items = PantryItem::query()->with('ingredient.picture')->get()
            ->filter(fn (PantryItem $item) => $item->ingredient !== null)
            ->sortBy(fn (PantryItem $item) => $item->ingredient?->name);

        $inventoried = Inventory::lastTaken($current->getOrFail());

        return Inertia::render('food/Pantry', [
            'locations' => array_map(fn (StorageLocation $location) => [
                'value' => $location->value,
                'label' => $location->label(),
                'inventoriedAt' => $inventoried[$location->value] ?? null,
                'items' => $items
                    ->filter(fn (PantryItem $item) => $item->location === $location)
                    ->map(fn (PantryItem $item) => self::present($item))
                    ->values()
                    ->all(),
            ], StorageLocation::cases()),
            'units' => Units::all(),
            'canEdit' => $user->can('create', PantryItem::class),
        ]);
    }

    /**
     * @return array{id: int, name: string|null, picture: array<string, mixed>|null, quantity: float|null, unit: string|null, location: string, alwaysHome: bool}
     */
    public static function present(PantryItem $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->ingredient?->name,
            'picture' => $item->ingredient?->picture?->toPicker(),
            'quantity' => $item->quantity === null ? null : (float) $item->quantity,
            'unit' => $item->unit,
            'location' => $item->location->value,
            'alwaysHome' => $item->always_home,
        ];
    }

    public function store(StorePantryItemRequest $request, CurrentFamily $current): RedirectResponse
    {
        // What is in brackets, e.g. "Smör (osaltat)", is not part of the name.
        $name = IngredientText::parse($request->string('name')->toString())['names'][0] ?? null;

        if (! $request->filled('ingredient_id') && $name === null) {
            return back()->withErrors(['name' => 'Skriv vad som finns hemma.']);
        }

        $ingredient = $request->filled('ingredient_id')
            ? Ingredient::query()->findOrFail($request->integer('ingredient_id'))
            : Ingredient::findOrCreateForFamily((string) $name, $current->getOrFail());

        // Adding what is already there updates it instead.
        PantryItem::query()->updateOrCreate(
            ['ingredient_id' => $ingredient->id],
            [
                'quantity' => $request->filled('quantity') ? $request->input('quantity') : null,
                'unit' => $request->filled('unit') ? $request->string('unit')->toString() : null,
                'always_home' => $request->boolean('always_home'),
                // The usual place for the grocery unless another is chosen.
                'location' => $request->enum('location', StorageLocation::class) ?? StorageLocation::for($ingredient->category),
            ],
        );

        return back();
    }

    /**
     * Mark or unmark a staple that is always at home.
     */
    public function update(Request $request, PantryItem $pantryItem): RedirectResponse
    {
        Gate::authorize('update', $pantryItem);

        $pantryItem->update(['always_home' => $request->boolean('always_home')]);

        return back();
    }

    public function destroy(PantryItem $pantryItem): RedirectResponse
    {
        Gate::authorize('delete', $pantryItem);

        $pantryItem->delete();

        return back();
    }
}
