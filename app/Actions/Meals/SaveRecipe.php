<?php

namespace App\Actions\Meals;

use App\Models\Family;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

/**
 * Saves a recipe with its ingredients. A name that is not in the basic list
 * or the family's own becomes a new ingredient of the family's.
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
                $ingredientId = $line['ingredient_id']
                    ?? Ingredient::findOrCreateForFamily((string) $line['name'], $family)->id;

                $recipe->ingredients()->create([
                    'ingredient_id' => $ingredientId,
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'],
                    'note' => $line['note'],
                    'sort_order' => $index,
                ]);
            }

            return $recipe;
        });
    }
}
