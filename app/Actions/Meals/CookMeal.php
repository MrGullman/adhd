<?php

namespace App\Actions\Meals;

use App\Food\Quantity;
use App\Models\MealPlanEntry;
use App\Models\PantryItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Lagad": what the recipe used, scaled to the portions, comes off the
 * pantry. Staples, groceries without an amount and amounts in units that
 * cannot be compared (e.g. "st" and "g") are left as they are. What it was
 * before is kept, so that "Ångra" puts it back.
 */
class CookMeal
{
    /**
     * @return list<string> Names of what was taken off the pantry.
     */
    public function cook(MealPlanEntry $entry): array
    {
        $recipe = $entry->recipe;

        if ($entry->cooked_at !== null || $recipe === null) {
            return [];
        }

        return DB::transaction(function () use ($entry, $recipe): array {
            $recipe->load(['ingredients.ingredient', 'ingredients.alternatives']);

            /** @var Collection<int, PantryItem> $pantry */
            $pantry = PantryItem::query()->with('ingredient')->lockForUpdate()->get()->keyBy('ingredient_id');
            $factor = $entry->portions / max(1, $recipe->portions);
            $before = [];
            $used = [];

            foreach ($recipe->ingredients as $line) {
                if ($line->quantity === null) {
                    continue;
                }

                $group = Quantity::group($line->unit);
                $need = Quantity::toBase((float) $line->quantity * $factor, $line->unit);

                // The first of "smör eller margarin" that is at home with an amount.
                $item = collect($line->ingredientIds())
                    ->map(fn (int $id) => $pantry->get($id))
                    ->first(fn (?PantryItem $candidate) => $candidate !== null
                        && ! $candidate->always_home
                        && $candidate->quantity !== null
                        && Quantity::group($candidate->unit) === $group);

                if (! $item instanceof PantryItem) {
                    continue;
                }

                $before[$item->ingredient_id] ??= [
                    'ingredient_id' => $item->ingredient_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'location' => $item->location->value,
                    'always_home' => $item->always_home,
                ];

                $left = Quantity::toBase((float) $item->quantity, $item->unit) - $need;
                $used[] = (string) $item->ingredient?->name;

                if ($left <= 0.001) {
                    $item->delete();
                    $pantry->forget($item->ingredient_id);
                } else {
                    $item->update(['quantity' => round(Quantity::fromBase($left, $item->unit), 2)]);
                }
            }

            $entry->forceFill(['cooked_at' => now(), 'pantry_before' => array_values($before)])->save();

            return array_values(array_unique($used));
        });
    }

    /**
     * "Ångra": the pantry as it was before the meal was cooked.
     */
    public function undo(MealPlanEntry $entry): void
    {
        if ($entry->cooked_at === null) {
            return;
        }

        DB::transaction(function () use ($entry): void {
            foreach ($entry->pantry_before ?? [] as $row) {
                PantryItem::query()->updateOrCreate(
                    ['ingredient_id' => $row['ingredient_id']],
                    [
                        'quantity' => $row['quantity'],
                        'unit' => $row['unit'],
                        'location' => $row['location'],
                        'always_home' => $row['always_home'],
                    ],
                );
            }

            $entry->forceFill(['cooked_at' => null, 'pantry_before' => null])->save();
        });
    }
}
