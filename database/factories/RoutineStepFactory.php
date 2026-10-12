<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\RoutineStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutineStep>
 */
class RoutineStepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'routine_id' => Routine::factory(),
            'label' => 'Borsta tänderna',
            'minutes' => 3,
            'type' => 'step',
            'optional' => false,
            'sort_order' => 0,
        ];
    }
}
