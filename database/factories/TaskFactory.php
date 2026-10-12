<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Task;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
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
            'title' => fake()->randomElement(['Borsta tänderna', 'Packa väskan', 'Duka bordet', 'Mata katten']),
            'date' => LocalTime::today(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
