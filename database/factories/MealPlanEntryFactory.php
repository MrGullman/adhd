<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\MealPlanEntry;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlanEntry>
 */
class MealPlanEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'date' => LocalTime::today(),
            'meal' => 'dinner',
            'title' => 'Pizza ute',
            'portions' => 4,
        ];
    }
}
