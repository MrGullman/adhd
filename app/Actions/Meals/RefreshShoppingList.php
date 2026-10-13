<?php

namespace App\Actions\Meals;

use App\Enums\ListType;
use App\Food\ShoppingListBuilder;
use App\Models\FamilyList;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * "Uppdatera handlingslistan": the week's things to buy replace the ones made
 * for the week before. What is ticked off, and what the family added itself,
 * is never touched. Meals that have been are not shopped for.
 */
class RefreshShoppingList
{
    public function __construct(private ShoppingListBuilder $builder) {}

    /**
     * @return int How many things are on the list for the week.
     */
    public function handle(CarbonImmutable $monday): int
    {
        $list = FamilyList::query()->where('type', ListType::Shopping)->firstOrFail();
        $needs = $this->builder->needs($monday, LocalTime::now()->startOfDay());

        DB::transaction(function () use ($list, $monday, $needs): void {
            $list->items()
                ->whereDate('meal_plan_week', $monday->toDateString())
                ->whereNull('completed_at')
                ->delete();

            foreach ($needs as $need) {
                $list->items()->create([
                    'name' => $need['name'],
                    'ingredient_id' => $need['ingredient']->id,
                    'picture_id' => $need['ingredient']->picture_id,
                    'quantity' => $need['quantity'],
                    'unit' => $need['unit'],
                    'meal_plan_week' => $monday->toDateString(),
                    'for_meals' => mb_substr(implode(', ', $need['meals']), 0, 150),
                ]);
            }
        });

        return count($needs);
    }
}
