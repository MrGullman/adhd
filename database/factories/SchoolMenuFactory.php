<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\SchoolMenu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Made-up schools only: real school names reveal where children go.
 *
 * @extends Factory<SchoolMenu>
 */
class SchoolMenuFactory extends Factory
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
            'name' => fake()->randomElement(['Ekbackeskolan', 'Solgläntans skola', 'Björkdalsskolan']),
            'provider' => 'matilda',
            'external_id' => Str::lower(Str::random(24)),
        ];
    }
}
