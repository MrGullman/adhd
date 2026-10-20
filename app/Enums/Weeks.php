<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Which weeks a routine is used, by ISO week number.
 */
enum Weeks: string
{
    case All = 'all';
    case Even = 'even';
    case Odd = 'odd';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Alla veckor',
            self::Even => 'Jämna veckor',
            self::Odd => 'Udda veckor',
        };
    }

    public function includes(CarbonInterface $date): bool
    {
        $week = (int) $date->format('W');

        return match ($this) {
            self::All => true,
            self::Even => $week % 2 === 0,
            self::Odd => $week % 2 === 1,
        };
    }
}
