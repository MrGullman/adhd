<?php

namespace Database\Factories;

use App\Enums\PictureSource;
use App\Models\Family;
use App\Models\Picture;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * A gallery picture by default. The file itself is not created: use
 * Storage::fake() and put a file at the path when a test needs it.
 *
 * @extends Factory<Picture>
 */
class PictureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'family_id' => null,
            'source' => PictureSource::Gallery,
            'path' => 'pictures/galleri/'.Str::uuid().'.webp',
            'processed_at' => now(),
            'label' => fake()->randomElement(['Tandborste', 'Mjölk', 'Ryggsäck', 'Badbyxor', 'Glad']),
        ];
    }

    /**
     * One of a family's own photos.
     */
    public function ofFamily(Family $family): static
    {
        return $this->state(fn () => [
            'family_id' => $family->id,
            'source' => PictureSource::Upload,
            'path' => "pictures/familjer/{$family->id}/".Str::uuid().'.webp',
        ]);
    }
}
