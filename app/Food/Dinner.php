<?php

namespace App\Food;

use App\Models\Family;
use App\Models\MealPlanEntry;
use Carbon\CarbonImmutable;

/**
 * "Middag ikväll": tonight's dinner from the family's week, or after dinner
 * (förval 19:00) tomorrow's.
 */
class Dinner
{
    public const DEFAULT_SWITCH = '19:00';

    /**
     * @return array<string, mixed>|null
     */
    public static function next(Family $family, CarbonImmutable $now): ?array
    {
        $switch = $family->settings['dinner_switch'] ?? self::DEFAULT_SWITCH;
        $tomorrow = $now->format('H:i') >= $switch;
        $day = $tomorrow ? $now->startOfDay()->addDay() : $now->startOfDay();

        $entry = MealPlanEntry::query()
            ->with('recipe.picture')
            ->whereDate('date', $day->toDateString())
            ->where('meal', 'dinner')
            ->orderBy('id')
            ->first();

        return $entry === null ? null : [
            ...$entry->present(),
            'label' => $tomorrow ? 'Middag imorgon' : 'Middag ikväll',
        ];
    }
}
