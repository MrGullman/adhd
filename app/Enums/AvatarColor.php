<?php

namespace App\Enums;

/**
 * Each family member has one of the logo's colours (see docs/STYLEGUIDE.md).
 */
enum AvatarColor: string
{
    case Teal = 'teal';
    case Orange = 'orange';
    case Green = 'green';
    case Yellow = 'yellow';
    case Purple = 'purple';

    public function label(): string
    {
        return match ($this) {
            self::Teal => 'Turkos',
            self::Orange => 'Orange',
            self::Green => 'Grön',
            self::Yellow => 'Gul',
            self::Purple => 'Lila',
        };
    }
}
