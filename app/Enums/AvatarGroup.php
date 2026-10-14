<?php

namespace App\Enums;

/**
 * Avatars in the shared gallery, by style: rounder for younger children,
 * more grown-up for older ones (docs/STYLEGUIDE.md, Barnens visningslägen).
 */
enum AvatarGroup: string
{
    case Child = 'child';
    case Youth = 'youth';
    case Adult = 'adult';

    public function label(): string
    {
        return match ($this) {
            self::Child => 'Barn',
            self::Youth => 'Ungdom',
            self::Adult => 'Vuxna',
        };
    }
}
