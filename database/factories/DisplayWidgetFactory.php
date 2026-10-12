<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\DisplayWidget;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<DisplayWidget>
 */
class DisplayWidgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => Device::factory()->state(['type' => 'display']),
            // Always the same family as the display.
            'family_id' => fn (array $attributes) => DB::table('devices')->where('id', $attributes['device_id'])->value('family_id'),
            'type' => 'shopping_list',
            'x' => 0,
            'y' => 0,
            'w' => 3,
            'h' => 4,
        ];
    }
}
