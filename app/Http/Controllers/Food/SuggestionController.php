<?php

namespace App\Http\Controllers\Food;

use App\Food\MealSuggestions;
use App\Http\Controllers\Controller;
use App\Models\MealPlanEntry;
use App\Support\LocalTime;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function __invoke(Request $request, MealSuggestions $suggestions): Response
    {
        return Inertia::render('food/Suggestions', [
            ...$suggestions->forFamily(),
            'today' => LocalTime::today(),
            'canPlan' => $request->user()?->can('create', MealPlanEntry::class) ?? false,
        ]);
    }
}
