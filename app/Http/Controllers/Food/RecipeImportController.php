<?php

namespace App\Http\Controllers\Food;

use App\Food\RecipeImport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Food\ImportRecipeRequest;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * "Importera från länk": the recipe on a web page fills in a new recipe,
 * which a parent checks and saves.
 */
class RecipeImportController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Recipe::class);

        return Inertia::render('food/recipes/Import');
    }

    public function store(ImportRecipeRequest $request, RecipeImport $import): RedirectResponse
    {
        try {
            $draft = $import->fromUrl(trim($request->string('url')->toString()));
        } catch (RuntimeException) {
            return back()->withErrors(['url' => 'Hittade inget recept på den sidan. Prova en annan länk eller skriv in receptet själv.']);
        }

        session()->flash('recipe_draft', $draft);

        return to_route('recipes.create');
    }
}
