<?php

namespace Database\Factories;

use App\Models\CommunityGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityGroup>
 */
class CommunityGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sömn',
            'description' => 'Kvällar och nätter.',
            'sort_order' => 10,
        ];
    }
}
