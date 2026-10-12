<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;
use App\Models\Membership;
use App\Models\User;
use App\Support\SchoolLunch;

/**
 * Skollunch: each child's lunch, today or after school the next school day's.
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

    public function data(DisplayContext $context, DisplayWidget $widget): array
    {
        $lunch = new SchoolLunch($context->family, $context->now);
        $label = null;
        $children = [];

        foreach ($context->children() as $child) {
            $meal = $lunch->forMenu($this->schoolMenuId($child));

            if ($meal !== null) {
                $label ??= $meal['label'];
                $children[] = ['child' => DisplayContext::member($child), ...$meal];
            }
        }

        return ['label' => $label ?? 'Skollunch', 'children' => $children];
    }

    private function schoolMenuId(User $child): ?int
    {
        $pivot = $child->getRelation('pivot');

        return $pivot instanceof Membership ? $pivot->school_menu_id : null;
    }
}
