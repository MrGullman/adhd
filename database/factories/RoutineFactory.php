<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Routine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Routine>
 */
class RoutineFactory extends Factory
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
            'name' => 'Morgonrutin',
            'day_part' => 'morning',
            'done_by' => '07:45:00',
            'ordered' => true,
        ];
    }

    /**
     * A template from the admin portal.
     */
    public function template(): static
    {
        return $this->state(fn () => ['family_id' => null]);
    }

    /**
     * With steps: [label, minutes, optional] each.
     *
     * @param  list<array{0: string, 1?: int, 2?: bool}>  $steps
     */
    public function withSteps(array $steps): static
    {
        return $this->afterCreating(function (Routine $routine) use ($steps): void {
            foreach ($steps as $index => $step) {
                $routine->steps()->create([
                    'label' => $step[0],
                    'minutes' => $step[1] ?? 5,
                    'optional' => $step[2] ?? false,
                    'sort_order' => $index,
                ]);
            }
        });
    }

    public function forChild(User $child): static
    {
        return $this->afterCreating(fn (Routine $routine) => $routine->children()->attach($child));
    }
}
