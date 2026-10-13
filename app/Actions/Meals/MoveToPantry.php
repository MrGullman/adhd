<?php

namespace App\Actions\Meals;

use App\Models\Ingredient;
use App\Models\ListItem;
use App\Models\PantryItem;

/**
 * Something ticked off on the shopping list is at home now.
 */
class MoveToPantry
{
    public function handle(ListItem $item): void
    {
        $ingredientId = $item->ingredient_id
            ?? Ingredient::query()->whereRaw('lower(name) = ?', [mb_strtolower(trim($item->name))])->value('id');

        if ($ingredientId === null) {
            return;
        }

        // Bought: there is enough, whatever was noted before.
        PantryItem::query()->updateOrCreate(['ingredient_id' => $ingredientId], ['quantity' => null, 'unit' => null]);
    }
}
