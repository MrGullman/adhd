<?php

namespace App\Food;

use App\Enums\StoreSection;
use App\Models\Ingredient;
use App\Models\MealPlanEntry;
use App\Models\PantryItem;
use App\Models\RecipeIngredient;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * What to buy for the week's meals: the recipes' ingredients scaled to the
 * planned portions, the same grocery added up (weight and volume converted),
 * minus what is at home. Staples and things at home without an amount count
 * as enough.
 */
class ShoppingListBuilder
{
    /**
     * @return list<array{ingredient: Ingredient, name: string, quantity: float|null, unit: string|null, meals: list<string>}>
     */
    public function needs(CarbonImmutable $monday, CarbonImmutable $from): array
    {
        $first = $from->greaterThan($monday) ? $from : $monday;

        $entries = MealPlanEntry::query()
            ->with(['recipe.ingredients.ingredient', 'recipe.ingredients.alternatives'])
            ->whereNotNull('recipe_id')
            ->whereBetween('date', [$first->toDateString(), $monday->addDays(6)->toDateString()])
            ->orderBy('date')
            ->get();

        /** @var Collection<int, PantryItem> $pantry */
        $pantry = PantryItem::query()->get()->keyBy('ingredient_id');
        $atHome = $pantry->keys()->map(fn ($id) => (int) $id)->all();

        /** @var array<string, array{ingredient: Ingredient, name: string, base: float|null, group: string, meals: list<string>}> $needs */
        $needs = [];

        foreach ($entries as $entry) {
            $recipe = $entry->recipe;

            if ($recipe === null) {
                continue;
            }

            $factor = $entry->portions / max(1, $recipe->portions);

            foreach ($recipe->ingredients as $line) {
                if ($line->ingredient === null || $this->alternativeAtHome($line, $atHome)) {
                    continue;
                }

                $hasAmount = $line->quantity !== null;
                $group = $hasAmount ? Quantity::group($line->unit) : '';
                $key = $line->ingredient_id.'|'.($hasAmount ? $group : '-');

                $needs[$key] ??= [
                    'ingredient' => $line->ingredient,
                    'name' => $line->alternatives->isEmpty() ? $line->ingredient->name : $line->displayName(),
                    'base' => $hasAmount ? 0.0 : null,
                    'group' => $group,
                    'meals' => [],
                ];

                if ($hasAmount) {
                    $needs[$key]['base'] = ($needs[$key]['base'] ?? 0.0) + Quantity::toBase((float) $line->quantity * $factor, $line->unit);
                }

                if (! in_array($entry->name(), $needs[$key]['meals'], true)) {
                    $needs[$key]['meals'][] = $entry->name();
                }
            }
        }

        $result = [];

        foreach ($needs as $need) {
            $base = $this->afterPantry($need, $pantry->get($need['ingredient']->id));

            if ($base === false) {
                continue;
            }

            [$quantity, $unit] = $base === null ? [null, null] : Quantity::readable($base, $need['group']);

            $result[] = [
                'ingredient' => $need['ingredient'],
                'name' => $need['name'],
                'quantity' => $quantity,
                'unit' => $unit,
                'meals' => $need['meals'],
            ];
        }

        $order = array_flip(array_map(fn (StoreSection $section) => $section->value, StoreSection::cases()));

        usort($result, fn (array $a, array $b) => [$order[$a['ingredient']->category->value], $a['name']] <=> [$order[$b['ingredient']->category->value], $b['name']]);

        return $result;
    }

    /**
     * "Smör eller margarin": nothing to buy when any of them is at home.
     *
     * @param  array<int>  $atHome  Ids of what is at home, in any order.
     */
    private function alternativeAtHome(RecipeIngredient $line, array $atHome): bool
    {
        return $line->alternatives->isNotEmpty() && $line->isAtHome($atHome);
    }

    /**
     * How much is left to buy (in the base unit), null to buy without an
     * amount, or false when what is at home is enough.
     *
     * @param  array{base: float|null, group: string}  $need
     */
    private function afterPantry(array $need, ?PantryItem $item): float|false|null
    {
        if ($item === null) {
            return $need['base'];
        }

        if ($item->always_home || $item->quantity === null || $need['base'] === null) {
            return false;
        }

        // Different kinds of units cannot be compared, e.g. "st" and "g".
        if (Quantity::group($item->unit) !== $need['group']) {
            return $need['base'];
        }

        $left = $need['base'] - Quantity::toBase((float) $item->quantity, $item->unit);

        return $left > 0 ? $left : false;
    }
}
