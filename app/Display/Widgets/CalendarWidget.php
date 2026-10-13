<?php

namespace App\Display\Widgets;

use App\Calendar\FamilyCalendar;
use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\CalendarEvent;
use App\Models\DisplayWidget;
use Carbon\CarbonImmutable;

/**
 * Kalender: today's and the next two days' events from the family calendars,
 * only those everyone at home may see.
 */
class CalendarWidget implements DisplayWidgetType
{
    private const DAYS = 3;

    private const PER_DAY = 4;

    public function type(): string
    {
        return 'calendar';
    }

    public function label(): string
    {
        return 'Kalender';
    }

    public function defaultSize(): array
    {
        return [4, 3];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $calendar = FamilyCalendar::between($context->today, $context->today->addDays(self::DAYS - 1));
        $days = [];

        for ($offset = 0; $offset < self::DAYS; $offset++) {
            $day = $context->today->addDays($offset);
            $events = $calendar->on($day, 'display', $context->activitiesOn($day)->map(fn (Activity $activity) => $activity->name)->all())
                ->reject(fn (CalendarEvent $event) => $offset === 0 && $this->isOver($event, $context->now));

            $days[] = [
                'date' => $day->toDateString(),
                'isToday' => $offset === 0,
                'events' => $events->take(self::PER_DAY)->map(fn (CalendarEvent $event) => $calendar->present($event, $day))->values()->all(),
                'more' => max(0, $events->count() - self::PER_DAY),
            ];
        }

        return ['days' => $days];
    }

    /**
     * What has already happened today is left out.
     */
    private function isOver(CalendarEvent $event, CarbonImmutable $now): bool
    {
        return ! $event->all_day && $event->ends_at !== null && $event->ends_at->lt($now);
    }
}
