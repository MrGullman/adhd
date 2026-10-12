<?php

namespace App\Enums;

enum ActivityType: string
{
    /** An activity, perhaps with a packing list (simskola, fotboll). */
    case Activity = 'activity';

    /** Someone is away (Mamma i Stockholm tisdag–onsdag). */
    case Away = 'away';

    /** Who picks up or drops off (Mormor hämtar 15:30). */
    case Pickup = 'pickup';

    /** A different day (studiedag, lov). */
    case DayOff = 'day_off';

    public function label(): string
    {
        return match ($this) {
            self::Activity => 'Aktivitet',
            self::Away => 'Bortrest',
            self::Pickup => 'Hämtning',
            self::DayOff => 'Annorlunda dag',
        };
    }
}
