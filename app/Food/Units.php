<?php

namespace App\Food;

/**
 * Units for recipes and the pantry. Weight and volume are converted within
 * their group when the shopping list is made (Milestone 10).
 */
class Units
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return ['st', 'g', 'kg', 'krm', 'tsk', 'msk', 'ml', 'dl', 'l', 'förp', 'paket', 'burk', 'påse', 'klyfta', 'kruka', 'skiva', 'nypa'];
    }
}
