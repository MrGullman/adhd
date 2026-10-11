<?php

namespace Database\Factories;

use App\Enums\ListType;
use App\Models\Family;
use App\Models\FamilyList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Every family already gets a shopping list when it is created, so this
 * factory makes other lists.
 *
 * @extends Factory<FamilyList>
 */
class FamilyListFactory extends Factory
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
            'type' => ListType::General,
            'name' => 'Att göra i helgen',
        ];
    }
}
