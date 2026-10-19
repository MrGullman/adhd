<?php

namespace Database\Factories;

use App\Enums\ArticleKind;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Ett ämne '.Str::random(4);

        return [
            'kind' => ArticleKind::Topic,
            'slug' => Str::slug($title),
            'title' => $title,
            'summary' => 'En kort beskrivning.',
            'body' => "Lite text.\n\n## En rubrik\n\nMer text.",
            'sort_order' => 1,
            'published' => true,
        ];
    }

    public function situation(): static
    {
        return $this->state(fn () => [
            'kind' => ArticleKind::Situation,
            'body' => null,
            'why' => 'Därför kan det hända.',
            'helps' => 'Det här kan hjälpa.',
            'in_app' => 'Så kan appen hjälpa.',
        ]);
    }
}
