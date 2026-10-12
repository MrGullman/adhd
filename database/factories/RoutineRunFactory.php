<?php

namespace Database\Factories;

use App\Models\Routine;
use App\Models\RoutineRun;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<RoutineRun>
 */
class RoutineRunFactory extends Factory
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
            // Always the same family as the routine.
            'family_id' => fn (array $attributes) => DB::table('routines')->where('id', $attributes['routine_id'])->value('family_id'),
            'date' => LocalTime::today(),
            'started_at' => now(),
        ];
    }
}
