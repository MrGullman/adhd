<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
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
            'name' => 'Tacos',
            'portions' => 4,
            'time_minutes' => 30,
        ];
    }

    /**
     * With ingredients from the basic list by name, e.g. ['Köttfärs' => [500, 'g']].
     *
     * @param  array<string, array{0: float|int|null, 1: string|null}>  $ingredients
     */
    public function withIngredients(array $ingredients): static
    {
        return $this->afterCreating(function (Recipe $recipe) use ($ingredients): void {
            $index = 0;

            foreach ($ingredients as $name => [$quantity, $unit]) {
                $ingredient = Ingredient::query()->withoutGlobalScopes()->whereNull('family_id')->where('name', $name)->firstOrFail();
                $recipe->ingredients()->create(['ingredient_id' => $ingredient->id, 'quantity' => $quantity, 'unit' => $unit, 'sort_order' => $index++]);
            }
        });
    }
}
