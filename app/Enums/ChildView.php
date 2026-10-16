<?php

namespace App\Enums;

/**
 * How a child's own views look (docs/STYLEGUIDE.md, Barnens visningslägen).
 * Parents choose per child, since maturity does not always follow age.
 */
enum ChildView: string
{
    /** Roughly 5–10 years: big pictures, Fredoka, one step at a time. */
    case Child = 'child';

    /** Roughly 10–14 years: the same structure, calmer and more grown-up. */
    case Youth = 'youth';

    public function label(): string
    {
        return match ($this) {
            self::Child => 'Barn',
            self::Youth => 'Ungdom',
        };
    }
}
