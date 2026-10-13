<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'calendar_id' => Calendar::factory(),
            'family_id' => fn (array $attributes) => Calendar::query()->withoutGlobalScopes()->whereKey($attributes['calendar_id'])->value('family_id'),
            'uid' => fake()->uuid(),
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
            'all_day' => false,
            'title' => 'Tandläkaren',
        ];
    }
}
