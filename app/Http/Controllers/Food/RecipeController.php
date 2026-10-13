<?php

namespace App\Http\Controllers\Food;

use App\Actions\Meals\SaveRecipe;
use App\Food\Units;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\StoreRecipeRequest;
use App\Http\Requests\Food\UpdateRecipeRequest;
use App\Models\PantryItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RecipeController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $search = trim($request->string('sok')->toString());

        return Inertia::render('food/recipes/Index', [
            'search' => $search,
            'recipes' => Recipe::query()
                ->with('picture')
                ->withCount('ingredients')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->get()
                ->map(fn (Recipe $recipe) => [
                    'id' => $recipe->id,
                    'name' => $recipe->name,
                    'picture' => $recipe->picture?->toPicker(),
                    'portions' => $recipe->portions,
                    'timeMinutes' => $recipe->time_minutes,
                    'ingredientCount' => $recipe->getAttribute('ingredients_count'),
                ]),
            'canCreate' => $user->can('create', Recipe::class),
        ]);
    }

    public function show(Request $request, Recipe $recipe): Response
    {
        Gate::authorize('view', $recipe);

        /** @var User $user */
        $user = $request->user();
        $recipe->load(['picture', 'ingredients.ingredient.picture', 'ingredients.alternatives']);
        $atHome = PantryItem::query()->pluck('ingredient_id')->map(fn ($id) => (int) $id)->all();

        return Inertia::render('food/recipes/Show', [
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'picture' => $recipe->picture?->toPicker(),
                'portions' => $recipe->portions,
                'timeMinutes' => $recipe->time_minutes,
                'instructions' => $recipe->instructions,
                'ingredients' => $recipe->ingredients->map(fn (RecipeIngredient $line) => [
                    'id' => $line->id,
                    'name' => $line->displayName(),
                    'picture' => $line->ingredient?->picture?->toPicker(),
                    'quantity' => $line->quantity === null ? null : (float) $line->quantity,
                    'unit' => $line->unit,
                    'note' => $line->note,
                    'atHome' => $line->isAtHome($atHome),
                ])->values(),
            ],
            'canEdit' => $user->can('update', $recipe),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Recipe::class);

        return Inertia::render('food/recipes/Create', ['units' => Units::all()]);
    }

    public function store(StoreRecipeRequest $request, CurrentFamily $current, SaveRecipe $save): RedirectResponse
    {
        $recipe = new Recipe;
        $recipe->created_by = $request->user()?->id;

        $save->handle($recipe, $current->getOrFail(), $request->recipe(), $request->lines());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$recipe->name} är sparat."]);

        return to_route('recipes.show', $recipe);
    }

    public function edit(Recipe $recipe): Response
    {
        Gate::authorize('update', $recipe);

        $recipe->load(['picture', 'ingredients.ingredient', 'ingredients.alternatives']);

        return Inertia::render('food/recipes/Edit', [
            'units' => Units::all(),
            'recipe' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'picture' => $recipe->picture?->toPicker(),
                'portions' => $recipe->portions,
                'timeMinutes' => $recipe->time_minutes,
                'instructions' => $recipe->instructions,
                'ingredients' => $recipe->ingredients->map(fn (RecipeIngredient $line) => [
                    'ingredientId' => $line->alternatives->isEmpty() ? $line->ingredient_id : null,
                    'name' => $line->displayName(),
                    'quantity' => $line->quantity === null ? null : (float) $line->quantity,
                    'unit' => $line->unit,
                    'note' => $line->note,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateRecipeRequest $request, CurrentFamily $current, SaveRecipe $save, Recipe $recipe): RedirectResponse
    {
        $save->handle($recipe, $current->getOrFail(), $request->recipe(), $request->lines());

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$recipe->name} är sparat."]);

        return to_route('recipes.show', $recipe);
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        Gate::authorize('delete', $recipe);

        $recipe->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$recipe->name} är borttaget."]);

        return to_route('recipes.index');
    }
}
