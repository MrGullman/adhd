<?php

namespace Tests\Feature\Family;

use App\Enums\DayPart;
use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DayPartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, DayPart}>
     */
    public static function timesWithDefaultDayParts(): array
    {
        return [
            'före morgonen' => ['05:30', DayPart::Morning],
            'morgonen börjar' => ['06:00', DayPart::Morning],
            'sista minuten på morgonen' => ['08:59', DayPart::Morning],
            'förmiddagen börjar' => ['09:00', DayPart::Forenoon],
            'eftermiddag' => ['14:30', DayPart::Afternoon],
            'kvällen börjar' => ['17:00', DayPart::Evening],
            'sent på kvällen' => ['23:45', DayPart::Evening],
        ];
    }

    #[DataProvider('timesWithDefaultDayParts')]
    public function test_a_time_belongs_to_a_part_of_the_day(string $time, DayPart $expected)
    {
        $this->assertSame($expected, Family::factory()->make()->dayPartFor($time));
    }

    public function test_a_parent_moves_when_the_evening_starts_and_coming_tasks_follow()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();
        $this->travelTo('2026-10-14 08:00:00');

        $today = Task::factory()->for($family)->create(['date' => '2026-10-14', 'day_part' => 'afternoon', 'time' => '16:30']);
        $yesterday = Task::factory()->for($family)->create(['date' => '2026-10-13', 'day_part' => 'afternoon', 'time' => '16:30']);

        $this->actingAs($parent)
            ->put(route('family.day-parts.update'), [
                'morning' => '06:30',
                'forenoon' => '09:00',
                'afternoon' => '12:00',
                'evening' => '16:00',
            ])
            ->assertRedirect(route('family.show'));

        $this->assertSame('16:00', $family->fresh()?->dayParts()['evening']);
        $this->assertSame(DayPart::Evening, $today->fresh()?->day_part);
        $this->assertSame(DayPart::Afternoon, $yesterday->fresh()?->day_part);
    }

    public function test_the_parts_of_the_day_must_come_in_order()
    {
        $family = Family::factory()->create();
        $parent = User::factory()->inFamily($family)->create();

        $this->actingAs($parent)
            ->put(route('family.day-parts.update'), [
                'morning' => '10:00',
                'forenoon' => '09:00',
                'afternoon' => '12:00',
                'evening' => '17:00',
            ])
            ->assertSessionHasErrors(['forenoon' => 'Förmiddagen måste börja efter morgonen.']);
    }

    public function test_only_parents_change_the_parts_of_the_day()
    {
        $family = Family::factory()->create();
        $editor = User::factory()->inFamily($family, FamilyRole::Editor)->create();

        $this->actingAs($editor)
            ->put(route('family.day-parts.update'), [
                'morning' => '07:00',
                'forenoon' => '09:00',
                'afternoon' => '12:00',
                'evening' => '17:00',
            ])
            ->assertForbidden();

        $this->assertNull($family->fresh()?->day_parts);
    }
}
