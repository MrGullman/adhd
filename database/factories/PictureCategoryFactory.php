<?php

namespace Database\Factories;

use App\Models\PictureCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PictureCategory>
 */
class PictureCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Morgon', 'Mat', 'Skola', 'Hygien', 'Känslor']),
            'sort_order' => 0,
        ];
    }
}
