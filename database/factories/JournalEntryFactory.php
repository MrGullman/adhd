<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\JournalEntry;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
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
            'date' => LocalTime::today(),
            'content' => 'Lugn morgon. Rutinen gick bra med bilderna.',
        ];
    }
}
