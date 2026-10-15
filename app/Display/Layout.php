<?php

namespace App\Display;

use App\Models\DisplayWidget;

/**
 * Where widgets go on the display's 12 × 6 grid. Widgets never overlap:
 * a widget that does not fit at its size is made smaller, and is only left
 * out of a layout when there is not a single free cell.
 */
class Layout
{
    /**
     * A free place for a widget of about this size: the size itself, or the
     * largest smaller one that fits. Null when the display is full.
     *
     * @param  list<array{x: int, y: int, w: int, h: int}>  $taken
     * @return array{x: int, y: int, w: int, h: int}|null
     */
    public static function place(array $taken, int $w, int $h): ?array
    {
        foreach (self::sizes($w, $h) as [$width, $height]) {
            for ($y = 0; $y + $height <= DisplayWidget::ROWS; $y++) {
                for ($x = 0; $x + $width <= DisplayWidget::COLUMNS; $x++) {
                    $spot = ['x' => $x, 'y' => $y, 'w' => $width, 'h' => $height];

                    if (self::fits($spot, $taken)) {
                        return $spot;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Puts overlapping or misplaced widgets somewhere free. The first ones
     * keep their place; later ones move or get smaller.
     *
     * @param  list<array{id: int, x: int, y: int, w: int, h: int}>  $widgets  In the order they keep their place.
     * @return array<int, array{x: int, y: int, w: int, h: int}> New places by id, only for the widgets that change.
     */
    public static function arrange(array $widgets): array
    {
        $taken = [];
        $misplaced = [];

        // First every widget that is where it should be keeps its place.
        foreach ($widgets as $widget) {
            $spot = ['x' => $widget['x'], 'y' => $widget['y'], 'w' => $widget['w'], 'h' => $widget['h']];

            if (self::inside($spot) && self::fits($spot, $taken)) {
                $taken[] = $spot;
            } else {
                $misplaced[] = $widget;
            }
        }

        // Then the others find a free place, smaller if needed.
        $changes = [];

        foreach ($misplaced as $widget) {
            $spot = self::place($taken, max(1, min($widget['w'], DisplayWidget::COLUMNS)), max(1, min($widget['h'], DisplayWidget::ROWS)));

            if ($spot === null) {
                // Not a single free cell: it stays where it is.
                continue;
            }

            $changes[$widget['id']] = $spot;
            $taken[] = $spot;
        }

        return $changes;
    }

    /**
     * The size itself first, then lower, then narrower.
     *
     * @return list<array{0: int, 1: int}>
     */
    private static function sizes(int $w, int $h): array
    {
        $sizes = [];

        for ($width = $w; $width >= 1; $width--) {
            for ($height = $h; $height >= 1; $height--) {
                $sizes[] = [$width, $height];
            }
        }

        return $sizes;
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int}  $spot
     */
    private static function inside(array $spot): bool
    {
        return $spot['x'] >= 0 && $spot['y'] >= 0 && $spot['w'] >= 1 && $spot['h'] >= 1
            && $spot['x'] + $spot['w'] <= DisplayWidget::COLUMNS
            && $spot['y'] + $spot['h'] <= DisplayWidget::ROWS;
    }

    /**
     * @param  array{x: int, y: int, w: int, h: int}  $spot
     * @param  list<array{x: int, y: int, w: int, h: int}>  $taken
     */
    private static function fits(array $spot, array $taken): bool
    {
        foreach ($taken as $other) {
            $apart = $other['x'] >= $spot['x'] + $spot['w']
                || $spot['x'] >= $other['x'] + $other['w']
                || $other['y'] >= $spot['y'] + $spot['h']
                || $spot['y'] >= $other['y'] + $other['h'];

            if (! $apart) {
                return false;
            }
        }

        return true;
    }
}
