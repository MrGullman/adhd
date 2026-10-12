<?php

namespace App\Enums;

/**
 * Parts of the day that tasks, routines and activities are grouped by.
 * Each family sets when the parts start (families.day_parts).
 */
enum DayPart: string
{
    case Morning = 'morning';
    case Forenoon = 'forenoon';
    case Afternoon = 'afternoon';
    case Evening = 'evening';

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morgon',
            self::Forenoon => 'Förmiddag',
            self::Afternoon => 'Eftermiddag',
            self::Evening => 'Kväll',
        };
    }

    /**
     * When the part starts unless the family has changed it (HH:MM).
     */
    public function defaultStart(): string
    {
        return match ($this) {
            self::Morning => '06:00',
            self::Forenoon => '09:00',
            self::Afternoon => '12:00',
            self::Evening => '17:00',
        };
    }
}
