<?php

namespace App\Http\Controllers;

use App\Food\ChildRecipes;
use App\Models\MealPlanEntry;
use App\Models\Recipe;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Laga mat: the recipes a child may cook. The week's dinners first, and
 * every recipe when a parent has chosen that for the child.
 */
class ChildRecipeController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response
    {
        /** @var User $child */
        $child = $request->user();
        $all = ChildRecipes::allowsAll($current->getOrFail()->id, $child);

        return Inertia::render('child/Recipes', [
            'week' => ChildRecipes::week()->map(fn (MealPlanEntry $entry) => [
                ...$entry->present(),
                'picture' => $entry->recipe?->picture?->toPicker(),
            ])->values(),
            'recipes' => $all
                ? Recipe::query()->with('picture')->orderBy('name')->get()->map(fn (Recipe $recipe) => [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'picture' => $recipe->picture?->toPicker(),
                ])->values()
                : [],
        ]);
    }
}
