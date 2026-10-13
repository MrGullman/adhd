<?php

namespace App\Http\Controllers\Food;

use App\Enums\StoreSection;
use App\Food\IngredientText;
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
 * Skafferi: what is at home, grouped by store section. Staples (basvaror)
 * always count as at home.
 */
class PantryController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $items = PantryItem::query()->with('ingredient.picture')->get()
            ->filter(fn (PantryItem $item) => $item->ingredient !== null)
            ->sortBy(fn (PantryItem $item) => $item->ingredient?->name);

        $sections = [];

        foreach (StoreSection::cases() as $section) {
            $inSection = $items->filter(fn (PantryItem $item) => $item->ingredient?->category === $section);

            if ($inSection->isNotEmpty()) {
                $sections[] = [
                    'value' => $section->value,
                    'label' => $section->label(),
                    'items' => $inSection->map(fn (PantryItem $item) => [
                        'id' => $item->id,
                        'name' => $item->ingredient?->name,
                        'picture' => $item->ingredient?->picture?->toPicker(),
                        'quantity' => $item->quantity === null ? null : (float) $item->quantity,
                        'unit' => $item->unit,
                        'alwaysHome' => $item->always_home,
                    ])->values()->all(),
                ];
            }
        }

        return Inertia::render('food/Pantry', [
            'sections' => $sections,
            'units' => Units::all(),
            'canEdit' => $user->can('create', PantryItem::class),
        ]);
    }

    public function store(StorePantryItemRequest $request, CurrentFamily $current): RedirectResponse
    {
        // What is in brackets, e.g. "Smör (osaltat)", is not part of the name.
        $name = IngredientText::parse($request->string('name')->toString())['names'][0] ?? null;

        if (! $request->filled('ingredient_id') && $name === null) {
            return back()->withErrors(['name' => 'Skriv vad som finns hemma.']);
        }

        $ingredientId = $request->filled('ingredient_id')
            ? $request->integer('ingredient_id')
            : Ingredient::findOrCreateForFamily((string) $name, $current->getOrFail())->id;

        // Adding what is already there updates it instead.
        PantryItem::query()->updateOrCreate(
            ['ingredient_id' => $ingredientId],
            [
                'quantity' => $request->filled('quantity') ? $request->input('quantity') : null,
                'unit' => $request->filled('unit') ? $request->string('unit')->toString() : null,
                'always_home' => $request->boolean('always_home'),
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
