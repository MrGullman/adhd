<?php

namespace App\Enums;

enum DeviceType: string
{
    /** Shared device, e.g. the kitchen tablet, where children log in with their code. */
    case Family = 'family';

    /** A child's own phone or tablet, paired with a QR code. */
    case Child = 'child';

    /** The home display (built in a later milestone). */
    case Display = 'display';

    public function label(): string
    {
        return match ($this) {
            self::Family => 'Familjeenhet',
            self::Child => 'Barnets enhet',
            self::Display => 'Hemskärm',
        };
    }
}
