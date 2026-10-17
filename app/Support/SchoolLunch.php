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

    /**
     * The next school days' lunches for a school, at most $days of them,
     * from today (or tomorrow after the switch). Weekends have no lunch and
     * are skipped; a holiday counts as a day.
     *
     * @return list<array{date: string, holiday: string|null, courses: list<array{name: string, option: string|null}>}>
     */
    public function upcoming(?int $schoolMenuId, int $days): array
    {
        if ($schoolMenuId === null) {
            return [];
        }

        $byDate = collect($this->meals[$schoolMenuId] ?? [])->groupBy(fn (SchoolMeal $meal) => $meal->date->toDateString());

        return array_values($byDate
            ->map(function ($meals, string $date) {
                // "Lunch" when the school has more than one meal a day.
                $meal = $meals->first(fn (SchoolMeal $meal) => $meal->meal_name === 'Lunch') ?? $meals->first();

                return ['date' => $date, 'holiday' => $meal->holiday_name, 'courses' => $meal->courses];
            })
            ->take($days)
            ->all());
    }

    /**
     * "Idag", "Imorgon" or the weekday, e.g. "Måndag".
     */
    public function dayLabel(string $date): string
    {
        return mb_convert_case(mb_substr($this->when($date), 0, 1), MB_CASE_UPPER).mb_substr($this->when($date), 1);
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
