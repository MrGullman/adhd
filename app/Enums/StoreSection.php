<?php

namespace App\Enums;

/**
 * Store sections, in the order you walk through a typical store. The shopping
 * list is sorted by them.
 */
enum StoreSection: string
{
    case Produce = 'produce';
    case Bread = 'bread';
    case Dairy = 'dairy';
    case Meat = 'meat';
    case Pantry = 'pantry';
    case Frozen = 'frozen';
    case Drinks = 'drinks';
    case Household = 'household';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Produce => 'Frukt & grönt',
            self::Bread => 'Bröd',
            self::Dairy => 'Mejeri',
            self::Meat => 'Kött & fisk',
            self::Pantry => 'Skafferi',
            self::Frozen => 'Frys',
            self::Drinks => 'Dryck',
            self::Household => 'Hushåll',
            self::Other => 'Övrigt',
        };
    }
}
