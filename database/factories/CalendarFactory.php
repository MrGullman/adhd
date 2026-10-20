<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Family;
use App\Models\User;
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
        ];
    }

    /**
     * "Visa för" these children.
     */
    public function shownTo(User ...$children): static
    {
        return $this->afterCreating(fn (Calendar $calendar) => $calendar->children()->attach(array_map(fn (User $child) => $child->id, $children)));
    }
}
