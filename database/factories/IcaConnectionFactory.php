<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\IcaConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IcaConnection>
 */
class IcaConnectionFactory extends Factory
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
            'client' => ['client_id' => 'test-client', 'client_secret' => 'test-secret', 'scope' => 'openid'],
            'token' => ['access_token' => 'test-access', 'refresh_token' => 'test-refresh'],
            'token_expires_at' => now()->addDays(30),
            'lists' => [['id' => 'list-1', 'title' => 'Handla']],
            'list_id' => 'list-1',
            'list_title' => 'Handla',
        ];
    }
}
