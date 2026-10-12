<?php

namespace App\Display\Widgets;

use App\Display\DisplayContext;
use App\Models\DisplayWidget;

/**
 * One kind of widget on the home display. Implementations only read data:
 * the display is read-only.
 */
interface DisplayWidgetType
{
    public function type(): string;

    public function label(): string;

    /**
     * Size when added: [width, height] in grid cells.
     *
     * @return array{0: int, 1: int}
     */
    public function defaultSize(): array;

    /**
     * Which setting the widget needs: "child", "member" or null.
     */
    public function setting(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function data(DisplayContext $context, DisplayWidget $widget): array;
}
