<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;
use App\Models\Reward;
use App\Points\Points;

/**
 * Poäng: each child's points and how far it is to the next reward.
 */
class PointsWidget implements DisplayWidgetType
{
    public function type(): string
    {
        return 'points';
    }

    public function label(): string
    {
        return 'Poäng';
    }

    public function defaultSize(): array
    {
        return [3, 3];
    }

    public function setting(): ?string
    {
        return null;
    }

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $balances = Points::balances();
        $rewards = Reward::query()->where('active', true)->orderBy('cost')->get(['id', 'name', 'cost']);

        return [
            'children' => $context->children()->map(function ($child) use ($balances, $rewards) {
                $points = $balances[$child->id] ?? 0;
                $next = $rewards->first(fn (Reward $reward) => $reward->cost > $points);

                return [
                    ...DisplayContext::member($child),
                    'points' => $points,
                    'next' => $next === null ? null : ['name' => $next->name, 'cost' => $next->cost],
                ];
            })->values()->all(),
        ];
    }
}
