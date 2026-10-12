<?php

namespace App\Support;

use App\Models\Family;
use App\Models\SchoolMeal;
use App\Models\SchoolMenu;
use Carbon\CarbonImmutable;

/**
 * Which school lunch to show: today's, or after the school day (förval
 * 15:00) the next school day's. Read from the database only.
 */
class SchoolLunch
{
    public const DEFAULT_SWITCH = '15:00';

    /**
     * Meals per school menu from the day shown and a week on.
     *
     * @var array<int, list<SchoolMeal>>
     */
    private array $meals = [];

    private CarbonImmutable $from;

    public function __construct(private Family $family, private CarbonImmutable $now)
    {
        $switch = $family->settings['school_lunch_switch'] ?? self::DEFAULT_SWITCH;
        $this->from = $now->format('H:i') >= $switch ? $now->startOfDay()->addDay() : $now->startOfDay();

        $menuIds = SchoolMenu::query()->pluck('id');

        $meals = SchoolMeal::query()
            ->whereIn('school_menu_id', $menuIds)
            ->whereBetween('date', [$this->from->toDateString(), $this->from->addDays(7)->toDateString()])
            ->orderBy('date')
            ->get();

        foreach ($meals as $meal) {
            $this->meals[$meal->school_menu_id][] = $meal;
        }
    }

    public static function now(Family $family): self
    {
        return new self($family, LocalTime::now());
    }

    /**
     * The next lunch for a school, with a label like "Skollunch idag",
     * "Skollunch imorgon" or "Skollunch måndag".
     *
     * @return array{label: string, date: string, holiday: string|null, courses: list<array{name: string, option: string|null}>}|null
     */
    public function forMenu(?int $schoolMenuId): ?array
    {
        if ($schoolMenuId === null) {
            return null;
        }

        $meals = $this->meals[$schoolMenuId] ?? [];
        $meal = collect($meals)->first(fn (SchoolMeal $meal) => $meal->meal_name === 'Lunch') ?? ($meals[0] ?? null);

        if ($meal === null) {
            return null;
        }

        return [
            'label' => 'Skollunch '.$this->when($meal->date->toDateString()),
            'date' => $meal->date->toDateString(),
            'holiday' => $meal->holiday_name,
            'courses' => $meal->courses,
        ];
    }

    private function when(string $date): string
    {
        $today = $this->now->startOfDay();

        return match ($date) {
            $today->toDateString() => 'idag',
            $today->addDay()->toDateString() => 'imorgon',
            default => CarbonImmutable::parse($date)->translatedFormat('l'),
        };
    }
}
