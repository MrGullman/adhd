<?php

namespace App\Display;

use App\Display\Widgets\ActivitiesWidget;
use App\Display\Widgets\CalendarWidget;
use App\Display\Widgets\ChildWidget;
use App\Display\Widgets\DisplayWidgetType;
use App\Display\Widgets\MealPlanWidget;
use App\Display\Widgets\PointsWidget;
use App\Display\Widgets\SchoolMealsWidget;
use App\Display\Widgets\ShoppingListWidget;
use App\Display\Widgets\TasksWidget;
use App\Display\Widgets\TomorrowWidget;
use App\Display\Widgets\WeekGridWidget;
use App\Display\Widgets\WeekWidget;
use App\Models\DisplayWidget;

/**
 * The widgets a home display can have.
 */
class WidgetRegistry
{
    /**
     * @return list<DisplayWidgetType>
     */
    public static function all(): array
    {
        return [
            new ChildWidget,
            new TasksWidget,
            new SchoolMealsWidget,
            new MealPlanWidget,
            new ActivitiesWidget,
            new TomorrowWidget,
            new WeekWidget,
            new WeekGridWidget,
            new CalendarWidget,
            new ShoppingListWidget,
            new PointsWidget,
        ];
    }

    public static function find(string $type): ?DisplayWidgetType
    {
        foreach (self::all() as $widget) {
            if ($widget->type() === $type) {
                return $widget;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_map(fn (DisplayWidgetType $widget) => $widget->type(), self::all());
    }

    /**
     * Each widget with its data. Only the widgets on the display are loaded.
     *
     * @param  iterable<DisplayWidget>  $widgets
     * @return list<array<string, mixed>>
     */
    public static function render(DisplayContext $context, iterable $widgets): array
    {
        $rendered = [];

        foreach ($widgets as $widget) {
            $type = self::find($widget->type);

            if ($type === null) {
                continue;
            }

            $rendered[] = [
                'id' => $widget->id,
                'type' => $widget->type,
                'label' => $type->label(),
                'x' => $widget->x,
                'y' => $widget->y,
                'w' => $widget->w,
                'h' => $widget->h,
                'settings' => $widget->settings ?? (object) [],
                'data' => $type->data($context, $widget),
            ];
        }

        return $rendered;
    }
}
