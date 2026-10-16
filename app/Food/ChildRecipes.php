<?php

namespace App\Food;

use App\Models\MealPlanEntry;
use App\Models\Membership;
use App\Models\User;
use App\Support\LocalTime;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The recipes a child may cook: the week's dinners, or every recipe when a
 * parent has chosen that for the child (family_user.all_recipes).
 */
class ChildRecipes
{
    public static function allowsAll(int $familyId, User $child): bool
    {
        return Membership::query()
            ->where('family_id', $familyId)
            ->where('user_id', $child->id)
            ->where('all_recipes', true)
            ->exists();
    }

    /**
     * This week's meals with a recipe, Monday to Sunday, in the current family.
     *
     * @return Collection<int, MealPlanEntry>
     */
    public static function week(): Collection
    {
        $monday = LocalTime::now()->startOfWeek(CarbonInterface::MONDAY);

        return MealPlanEntry::query()
            ->with('recipe.picture')
            ->whereNotNull('recipe_id')
            ->whereBetween('date', [$monday->toDateString(), $monday->addDays(6)->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    public static function inWeek(int $recipeId): bool
    {
        return self::week()->contains('recipe_id', $recipeId);
    }
}
