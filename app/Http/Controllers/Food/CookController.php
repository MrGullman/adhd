<?php

namespace App\Http\Controllers\Food;

use App\Enums\FamilyRole;
use App\Food\CookingSteps;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\CookRecipeRequest;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Support\CurrentFamily;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laga: a recipe one step at a time on the phone or the tablet, for adults
 * and children. First the ingredients to take out, then each step with its
 * timers. From the week's meal plan, adults can mark it Lagad at the end.
 */
class CookController extends Controller
{
    public function show(CookRecipeRequest $request, CurrentFamily $current, Recipe $recipe): Response
    {
        /** @var User $user */
        $user = $request->user();
        $recipe->load(['picture', 'ingredients.ingredient.picture', 'ingredients.alternatives']);

        $entry = $request->filled('maltid') ? MealPlanEntry::query()->find($request->integer('maltid')) : null;
        $portions = $request->filled('portioner')
            ? $request->integer('portioner')
            : ($entry->portions ?? $recipe->portions);
        $factor = $portions / max(1, $recipe->portions);
        $isChild = $current->role() === FamilyRole::Child;

        return Inertia::render('cook/Recipe', [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'picture' => $recipe->picture?->toPicker(),
                'portions' => $portions,
                'ingredients' => $recipe->ingredients->map(fn (RecipeIngredient $line) => [
                    'id' => $line->id,
                    'name' => $line->displayName(),
                    'picture' => $line->ingredient?->picture?->toPicker(),
                    'quantity' => $line->quantity === null ? null : round((float) $line->quantity * $factor, 2),
                    'unit' => $line->unit,
                    'note' => $line->note,
                ])->values(),
                'steps' => CookingSteps::from($recipe->instructions),
            ],
            'entry' => $entry === null ? null : [
                'id' => $entry->id,
                'cooked' => $entry->cooked_at !== null,
                'canMarkCooked' => $user->can('update', $entry),
            ],
            'backHref' => $isChild ? route('child.recipes') : route('recipes.show', $recipe),
        ]);
    }
}
