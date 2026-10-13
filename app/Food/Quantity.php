<?php

namespace App\Food;

/**
 * Amounts that can be added up: weight within g and kg, volume within krm,
 * tsk, msk, ml, dl and l. Other units (st, förp …) only with the same unit.
 */
class Quantity
{
    /**
     * Grams per unit.
     */
    private const WEIGHT = ['g' => 1, 'kg' => 1000];

    /**
     * Millilitres per unit (Swedish kitchen measures).
     */
    private const VOLUME = ['krm' => 1, 'tsk' => 5, 'msk' => 15, 'ml' => 1, 'dl' => 100, 'l' => 1000];

    /**
     * What a unit can be added up with: "weight", "volume", or the unit itself.
     */
    public static function group(?string $unit): string
    {
        $unit = mb_strtolower(trim((string) $unit));

        return match (true) {
            isset(self::WEIGHT[$unit]) => 'weight',
            isset(self::VOLUME[$unit]) => 'volume',
            default => $unit,
        };
    }

    /**
     * In the group's base unit (g or ml), or as is for other units.
     */
    public static function toBase(float $quantity, ?string $unit): float
    {
        $unit = mb_strtolower(trim((string) $unit));

        return $quantity * (self::WEIGHT[$unit] ?? self::VOLUME[$unit] ?? 1);
    }

    /**
     * A base amount in a readable unit: 1 200 g is 1,2 kg, 450 ml is 4,5 dl,
     * 1,5 påse is 2 påse.
     *
     * @return array{0: float, 1: string|null}
     */
    public static function readable(float $base, string $group): array
    {
        [$amount, $unit] = match (true) {
            $group === 'weight' && $base >= 1000 => [$base / 1000, 'kg'],
            $group === 'weight' => [$base, 'g'],
            $group === 'volume' && $base >= 1000 => [$base / 1000, 'l'],
            $group === 'volume' && $base >= 100 => [$base / 100, 'dl'],
            $group === 'volume' && $base >= 15 && fmod($base, 15) === 0.0 => [$base / 15, 'msk'],
            $group === 'volume' && $base >= 5 && fmod($base, 5) === 0.0 => [$base / 5, 'tsk'],
            $group === 'volume' && $base >= 15 => [$base / 100, 'dl'],
            $group === 'volume' => [$base, 'krm'],
            // Pieces, bags and packs are bought whole: 1,5 påse is 2.
            default => [ceil(round($base, 2)), $group === '' ? null : $group],
        };

        return [round($amount, 1), $unit];
    }
}
