<?php

namespace App\Http\Controllers\Food;

use App\Food\MealSuggestions;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function __invoke(MealSuggestions $suggestions): Response
    {
        return Inertia::render('food/Suggestions', $suggestions->forFamily());
    }
}
