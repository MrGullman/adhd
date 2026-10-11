<?php

namespace App\Support;

/**
 * Rules for children's login codes: 4–6 digits, not the same digit repeated
 * and not a straight ascending or descending run (1234, 4321).
 */
class ChildPins
{
    public const MIN_LENGTH = 4;

    public const MAX_LENGTH = 6;

    public static function hasValidFormat(string $pin): bool
    {
        return preg_match('/^\d{'.self::MIN_LENGTH.','.self::MAX_LENGTH.'}$/', $pin) === 1;
    }

    public static function isTooSimple(string $pin): bool
    {
        $digits = array_map('intval', str_split($pin));

        if (count(array_unique($digits)) === 1) {
            return true;
        }

        $steps = [];

        for ($i = 1; $i < count($digits); $i++) {
            $steps[] = $digits[$i] - $digits[$i - 1];
        }

        $steps = array_unique($steps);

        return count($steps) === 1 && in_array(reset($steps), [1, -1], true);
    }

    /**
     * A random code that passes the rules.
     */
    public static function generate(int $length = self::MIN_LENGTH): string
    {
        do {
            $pin = '';

            for ($i = 0; $i < $length; $i++) {
                $pin .= (string) random_int(0, 9);
            }
        } while (self::isTooSimple($pin));

        return $pin;
    }
}
