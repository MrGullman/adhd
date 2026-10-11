<?php

namespace Database\Factories;

use App\Models\FamilyList;
use App\Models\ListItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'list_id' => FamilyList::factory(),
            // Always the same family as the list.
            'family_id' => fn (array $attributes) => DB::table('lists')->where('id', $attributes['list_id'])->value('family_id'),
            'name' => fake()->randomElement(['Mjölk', 'Bröd', 'Bananer', 'Ost', 'Pasta']),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
