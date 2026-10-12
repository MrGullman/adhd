<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Family;
use App\Models\User;
use App\Support\LocalTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
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
            'type' => ActivityType::Activity,
            'name' => 'Simskola',
            'date' => LocalTime::today(),
            'time' => '17:15:00',
            'visible_to_children' => true,
            'unusual' => false,
        ];
    }

    /**
     * Every week on an ISO weekday (1 = Monday), with no first or last day.
     */
    public function weekly(int $weekday): static
    {
        return $this->state(fn () => ['weekday' => $weekday, 'date' => null]);
    }

    /**
     * @param  list<string>  $labels
     */
    public function withItems(array $labels): static
    {
        return $this->afterCreating(function (Activity $activity) use ($labels): void {
            foreach ($labels as $index => $label) {
                $activity->items()->create(['label' => $label, 'sort_order' => $index]);
            }
        });
    }

    public function concerning(User ...$participants): static
    {
        return $this->afterCreating(fn (Activity $activity) => $activity->participants()->attach(array_map(fn (User $user) => $user->id, $participants)));
    }
}
