<?php

namespace App\Food;

use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Collection;

/**
 * What can be cooked with what is at home: "Kan lagas nu" and "Saknar 1–2
 * saker". Something at home counts as enough (quantities are compared when
 * the shopping list is made), and staples always count as at home.
 */
class MealSuggestions
{
    /**
     * At most this many missing things for "Saknar 1–2 saker".
     */
    public const MAX_MISSING = 2;

    /**
     * @return array{canCook: list<array<string, mixed>>, missingFew: list<array<string, mixed>>}
     */
    public function forFamily(): array
    {
        $atHome = PantryItem::query()->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all();

        /** @var Collection<int, Recipe> $recipes */
        $recipes = Recipe::query()->with(['picture', 'ingredients.ingredient', 'ingredients.alternatives'])->orderBy('name')->get();

        $canCook = [];
        $missingFew = [];

        foreach ($recipes as $recipe) {
            if ($recipe->ingredients->isEmpty()) {
                continue;
            }

            // A line is at home when any of its groceries is ("smör eller margarin").
            $missing = $recipe->ingredients
                ->reject(fn (RecipeIngredient $line) => $line->isAtHome($atHome))
                ->unique(fn (RecipeIngredient $line) => implode(',', $line->ingredientIds()))
                ->values();

            $entry = [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'picture' => $recipe->picture?->toPicker(),
                'timeMinutes' => $recipe->time_minutes,
                'missing' => $missing->map(fn (RecipeIngredient $line) => $line->displayName())->all(),
            ];

            if ($missing->isEmpty()) {
                $canCook[] = $entry;
            } elseif ($missing->count() <= self::MAX_MISSING) {
                $missingFew[] = $entry;
            }
        }

        usort($missingFew, fn (array $a, array $b) => count($a['missing']) <=> count($b['missing']));

        return ['canCook' => $canCook, 'missingFew' => $missingFew];
    }
}
