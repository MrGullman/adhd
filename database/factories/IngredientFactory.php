<?php

namespace Database\Factories;

use App\Enums\StoreSection;
use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * An ingredient of the shared basic list by default.
 *
 * @extends Factory<Ingredient>
 */
class IngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => null,
            'name' => fake()->unique()->word(),
            'category' => StoreSection::Other,
            'default_unit' => 'st',
        ];
    }
}
