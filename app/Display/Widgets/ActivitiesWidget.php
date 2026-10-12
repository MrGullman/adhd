<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\DisplayWidget;

/**
 * Händer idag: activities and events, with how far the packing has come.
 */
class ActivitiesWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'activities';
    }

    public function label(): string
    {
        return 'Händer idag';
    }

    public function defaultSize(): array
    {
        return [4, 2];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        return [
            'activities' => $context->activitiesOn($context->today)
                ->map(fn (Activity $activity) => $context->calendar()->present($activity, $context->today))
                ->values()
                ->all(),
        ];
    }
}
