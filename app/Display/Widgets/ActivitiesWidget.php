<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\Activity;
use App\Models\DisplayWidget;
use App\Models\User;

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
        // One adult's day on the display: only what they take part in and is marked "Visa för barnen".
        $memberId = $widget->setting('member_id');
        $activities = $context->activitiesOn($context->today);

        if ($memberId !== null) {
            $activities = $activities->filter(fn (Activity $activity) => $activity->visible_to_children
                && $activity->participants->contains(fn (User $user) => $user->id === (int) $memberId));
        }

        return [
            'activities' => $activities
                ->map(fn (Activity $activity) => $context->calendar()->present($activity, $context->today))
                ->values()
                ->all(),
        ];
    }
}
