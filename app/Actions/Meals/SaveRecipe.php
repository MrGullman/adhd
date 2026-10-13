<?php

namespace App\Actions\Meals;

use App\Food\IngredientText;
use App\Models\Family;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

/**
 * Saves a recipe with its ingredients. A name that is not in the basic list
 * or the family's own becomes a new ingredient of the family's. "Smör el
 * margarin (till stekning)" is Smör with the alternative Margarin and the
 * note "till stekning".
 */
class SaveRecipe
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array{ingredient_id: int|null, name: string|null, quantity: string|null, unit: string|null, note: string|null}>  $lines
     */
    public function handle(Recipe $recipe, Family $family, array $attributes, array $lines): Recipe
    {
        return DB::transaction(function () use ($recipe, $family, $attributes, $lines): Recipe {
            $recipe->fill($attributes)->save();
            $recipe->ingredients()->delete();

            foreach ($lines as $index => $line) {
                $parsed = IngredientText::parse((string) $line['name']);
                $names = $parsed['names'];

                // Chosen from the list and nothing more typed: the chosen grocery.
                $ingredientIds = $line['ingredient_id'] !== null && count($names) <= 1 && $parsed['note'] === null
                    ? [$line['ingredient_id']]
                    : array_map(fn (string $name) => Ingredient::findOrCreateForFamily($name, $family)->id, $names);

                $ingredientIds = array_values(array_unique($ingredientIds));

                if ($ingredientIds === []) {
                    continue;
                }

                $saved = $recipe->ingredients()->create([
                    'ingredient_id' => $ingredientIds[0],
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'],
                    'note' => implode(', ', array_filter([$line['note'], $parsed['note']])) ?: null,
                    'sort_order' => $index,
                ]);

                $saved->alternatives()->sync(array_slice($ingredientIds, 1));
            }

            return $recipe;
        });
    }
}
