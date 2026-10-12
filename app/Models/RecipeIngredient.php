<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An ingredient in a recipe, e.g. "400 g Köttfärs". Reached through its recipe.
 *
 * @property int $id
 * @property int $recipe_id
 * @property int $ingredient_id
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $note
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ingredient_id', 'quantity', 'unit', 'note', 'sort_order'])]
class RecipeIngredient extends Model
{
    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
