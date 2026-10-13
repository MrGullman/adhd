<?php

namespace Database\Factories;

use App\Models\CommunityPost;
use App\Models\CommunityThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityPost>
 */
class CommunityPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'community_thread_id' => CommunityThread::factory(),
            'user_id' => User::factory(),
            'body' => 'Vi har provat bildschema och det hjälper.',
        ];
    }
}
