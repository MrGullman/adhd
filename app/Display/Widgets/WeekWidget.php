<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\DisplayWidget;

/**
 * Veckan: only activities, events and what is different, at most three a day.
 */
class WeekWidget implements DisplayWidgetType
{
    private const PER_DAY = 3;

    public function type(): string
    {
        return 'week';
    }

    public function label(): string
    {
        return 'Veckan';
    }

    public function defaultSize(): array
    {
        return [12, 2];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $days = [];
        $monday = $context->today->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $day = $monday->addDays($i);
            $activities = $context->activitiesOn($day);

            $days[] = [
                'date' => $day->toDateString(),
                'isToday' => $day->isSameDay($context->today),
                'isPast' => $day->lt($context->today),
                'unusual' => $activities->contains(fn (Activity $activity) => $activity->unusual),
                'activities' => $activities->take(self::PER_DAY)
                    ->map(fn (Activity $activity) => $context->calendar()->present($activity, $day))
                    ->values()
                    ->all(),
                'more' => max(0, $activities->count() - self::PER_DAY),
            ];
        }

        return ['days' => $days];
    }
}
