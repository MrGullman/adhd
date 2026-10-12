<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\DisplayWidget;

/**
 * Imorgon: what to get ready for, and whether it is packed.
 */
class TomorrowWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'tomorrow';
    }

    public function label(): string
    {
        return 'Imorgon';
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
        $tomorrow = $context->today->addDay();

        return [
            'activities' => $context->activitiesOn($tomorrow)
                ->map(fn (Activity $activity) => $context->calendar()->present($activity, $tomorrow))
                ->values()
                ->all(),
        ];
    }
}
