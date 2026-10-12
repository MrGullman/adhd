<?php

namespace App\Enums;

enum ListType: string
{
    /** The family's shopping list. Every family has exactly one, and children can add to it. */
    case Shopping = 'shopping';

    /** Any other list, e.g. "Att göra i helgen". */
    case General = 'general';
}
