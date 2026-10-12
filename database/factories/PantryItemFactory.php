<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Ingredient;
use App\Models\PantryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PantryItem>
 */
class PantryItemFactory extends Factory
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
            'ingredient_id' => Ingredient::factory(),
            'always_home' => false,
        ];
    }

    /**
     * Something from the basic list, by name.
     */
    public function of(string $name): static
    {
        return $this->state(fn () => [
            'ingredient_id' => Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->where('name', $name)->value('id'),
        ]);
    }
}
