<?php

namespace App\Http\Controllers\Food;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Autocomplete: the basic list and the family's own, so the same grocery is
 * always called the same.
 */
class IngredientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->string('sok')->toString());

        $ingredients = Ingredient::query()
            ->with('picture')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderByRaw('name like ? desc', [$search.'%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'ingredients' => $ingredients->map(fn (Ingredient $ingredient) => $ingredient->toOption())->values(),
        ]);
    }
}
