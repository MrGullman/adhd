<?php

namespace Database\Factories;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Use Device::issue() in tests that need the plain token for the device cookie.
 *
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
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
            'type' => DeviceType::Family,
            'name' => 'Köksplattan',
            'token_hash' => hash('sha256', Str::random(64)),
        ];
    }
}
