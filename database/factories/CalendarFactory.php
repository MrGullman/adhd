<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Calendar>
 */
class CalendarFactory extends Factory
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
            'name' => 'Familjen',
            'url' => 'https://calendar.example.com/'.fake()->uuid().'/basic.ics',
            'visible_to_children' => false,
        ];
    }
}
