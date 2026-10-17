<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;
use App\Models\Membership;
use App\Models\User;
use App\Support\SchoolLunch;

/**
 * Skollunch: each child's lunch, today or after school the next school day's.
 * Drawn wider, it shows more school days: up to a whole week at full width.
 */
class SchoolMealsWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'school_meals';
    }

    public function label(): string
    {
        return 'Skollunch';
    }

    public function defaultSize(): array
    {
        return [4, 2];
    }

    public function setting(): ?string
    {
        return null;
    }

    /**
     * School days for a width of 1–12 columns: 4 columns or less shows one
     * day, and every two columns more shows another, up to five.
     */
    public static function days(int $width): int
    {
        return max(1, min(5, (int) ceil(($width - 2) / 2)));
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $lunch = new SchoolLunch($context->family, $context->now);
        $count = self::days($widget->w);

        /** @var array<string, list<array<string, mixed>>> $byDate Each child's lunch per day. */
        $byDate = [];

        foreach ($context->children() as $child) {
            foreach ($lunch->upcoming($this->schoolMenuId($child), $count) as $meal) {
                $byDate[$meal['date']][] = [
                    'child' => DisplayContext::member($child),
                    'holiday' => $meal['holiday'],
                    'courses' => $meal['courses'],
                ];
            }
        }

        ksort($byDate);
        $days = array_slice($byDate, 0, $count, true);
        $first = array_key_first($days);

        return [
            // One day: "Skollunch idag" as before. More days: each has its own heading.
            'label' => $first === null ? 'Skollunch' : ($count === 1 ? 'Skollunch '.mb_strtolower($lunch->dayLabel($first)) : 'Skollunch'),
            'days' => array_map(fn (string $date) => [
                'date' => $date,
                'label' => $lunch->dayLabel($date),
                'children' => $days[$date],
            ], array_keys($days)),
        ];
    }

    private function schoolMenuId(User $child): ?int
    {
        $pivot = $child->getRelation('pivot');

        return $pivot instanceof Membership ? $pivot->school_menu_id : null;
    }
}
