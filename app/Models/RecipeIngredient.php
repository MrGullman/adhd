<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * An ingredient in a recipe, e.g. "400 g Köttfärs" or "50 g Smör eller
 * margarin". Reached through its recipe.
 *
 * @property int $id
 * @property int $recipe_id
 * @property int $ingredient_id
 * @property string|null $quantity
 * @property string|null $unit
 * @property string|null $note
 * @property string|null $section A heading such as "Fyllning", or null.
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ingredient_id', 'quantity', 'unit', 'note', 'section', 'sort_order'])]
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
     * Other groceries that will do, e.g. Margarin for Smör.
     *
     * @return BelongsToMany<Ingredient, $this>
     */
    public function alternatives(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient_alternatives')->orderBy('recipe_ingredient_alternatives.id');
    }

    /**
     * The grocery and its alternatives.
     *
     * @return list<int>
     */
    public function ingredientIds(): array
    {
        return [$this->ingredient_id, ...array_map(intval(...), $this->alternatives->modelKeys())];
    }

    /**
     * "Smör eller margarin".
     */
    public function displayName(): string
    {
        $names = [$this->ingredient?->name, ...$this->alternatives->map(fn (Ingredient $alternative) => mb_strtolower($alternative->name))->all()];

        return implode(' eller ', array_filter($names));
    }

    /**
     * Whether it is at home: any of the groceries will do.
     *
     * @param  array<int>  $atHome  Ids of what is at home, in any order.
     */
    public function isAtHome(array $atHome): bool
    {
        return array_intersect($this->ingredientIds(), $atHome) !== [];
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
