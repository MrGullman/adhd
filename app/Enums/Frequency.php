<?php

namespace App\Enums;

/**
 * How often a recurring task comes back.
 */
enum Frequency: string
{
    case Daily = 'daily';

    /** On chosen weekdays, every week or every other week. */
    case Weekly = 'weekly';
}
