<?php

namespace Database\Factories;

use App\Models\CommunityGroup;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityThread>
 */
class CommunityThreadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_group_id' => CommunityGroup::factory(),
            'user_id' => User::factory(),
            'title' => 'Hur får ni till kvällarna?',
            'last_posted_at' => now(),
        ];
    }
}
