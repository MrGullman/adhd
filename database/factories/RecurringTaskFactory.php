<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Models\Family;
use App\Models\RecurringTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTask>
 */
class RecurringTaskFactory extends Factory
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
            'title' => 'Borsta tänderna',
            'frequency' => Frequency::Daily,
            'week_interval' => 1,
            'starts_on' => '2026-10-12',
        ];
    }

    /**
     * On the given ISO weekdays (1 = Monday), every week or every other week.
     *
     * @param  list<int>  $weekdays
     */
    public function weekly(array $weekdays, int $interval = 1): static
    {
        return $this->state(fn () => [
            'frequency' => Frequency::Weekly,
            'weekdays' => $weekdays,
            'week_interval' => $interval,
        ]);
    }
}
