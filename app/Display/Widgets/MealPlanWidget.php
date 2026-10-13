<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Food\Dinner;
use App\Models\DisplayWidget;
use App\Models\MealPlanEntry;

/**
 * Middag: tonight's dinner with its picture (after dinner tomorrow's), and the
 * coming days of the family's week. Never the school meals.
 */
class MealPlanWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'meal_plan';
    }

    public function label(): string
    {
        return 'Middag';
    }

    public function defaultSize(): array
    {
        return [4, 2];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $dinner = Dinner::next($context->family, $context->now);
        $from = $context->today->addDay();

        return [
            'dinner' => $dinner,
            'coming' => MealPlanEntry::query()
                ->with('recipe')
                ->where('meal', 'dinner')
                ->whereBetween('date', [$from->toDateString(), $from->addDays(5)->toDateString()])
                ->when($dinner !== null, fn ($query) => $query->whereKeyNot($dinner['id']))
                ->orderBy('date')
                ->limit(4)
                ->get()
                ->map(fn (MealPlanEntry $entry) => ['date' => $entry->date->toDateString(), 'name' => $entry->name()])
                ->all(),
        ];
    }
}
