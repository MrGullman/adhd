<?php

namespace Database\Factories;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\FamilyInvitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Use FamilyInvitation::issue() in tests that need the plain token for the link.
 *
 * @extends Factory<FamilyInvitation>
 */
class FamilyInvitationFactory extends Factory
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
            'email' => fake()->unique()->safeEmail(),
            'role' => FamilyRole::Viewer,
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(FamilyInvitation::VALID_DAYS),
        ];
    }
}
