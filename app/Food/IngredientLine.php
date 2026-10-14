<?php

namespace App\Food;

/**
 * A line from a recipe on the web, e.g. "1 ½ dl grädde", "2 gula lökar,
 * finhackade" or "1 burk krossade tomater (400 g)", as amount, unit, name
 * and note. "Salt och peppar" without an amount becomes two lines.
 */
class IngredientLine
{
    private const FRACTIONS = ['½' => 0.5, '¼' => 0.25, '¾' => 0.75, '⅓' => 0.333, '⅔' => 0.667, '⅛' => 0.125];

    /**
     * Words for the units the app knows. "cl" becomes ml.
     */
    private const UNITS = [
        'g' => 'g', 'gr' => 'g', 'gram' => 'g',
        'kg' => 'kg', 'kilo' => 'kg', 'kilogram' => 'kg',
        'krm' => 'krm', 'kryddmått' => 'krm',
        'tsk' => 'tsk', 'tesked' => 'tsk', 'teskedar' => 'tsk',
        'msk' => 'msk', 'matsked' => 'msk', 'matskedar' => 'msk',
        'ml' => 'ml', 'milliliter' => 'ml',
        'cl' => 'cl', 'centiliter' => 'cl',
        'dl' => 'dl', 'deciliter' => 'dl',
        'l' => 'l', 'liter' => 'l',
        'st' => 'st', 'styck' => 'st', 'stycken' => 'st',
        'förp' => 'förp', 'förpackning' => 'förp', 'förpackningar' => 'förp',
        'paket' => 'paket', 'pkt' => 'paket',
        'burk' => 'burk', 'burkar' => 'burk',
        'påse' => 'påse', 'påsar' => 'påse',
        'klyfta' => 'klyfta', 'klyftor' => 'klyfta',
        'kruka' => 'kruka', 'krukor' => 'kruka',
        'skiva' => 'skiva', 'skivor' => 'skiva',
        'nypa' => 'nypa', 'nypor' => 'nypa',
    ];

    /**
     * @return list<array{name: string, quantity: float|null, unit: string|null, note: string|null}>
     */
    public static function parse(string $line): array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        if ($text === '') {
            return [];
        }

        [$quantity, $text] = self::quantity($text);
        [$unit, $text] = $quantity === null ? [null, $text] : self::unit($text);

        if ($unit === 'cl') {
            [$quantity, $unit] = [$quantity * 10, 'ml'];
        }

        // "2 gula lökar" is two pieces.
        if ($quantity !== null && $unit === null) {
            $unit = 'st';
        }

        // "gul lök, finhackad": what comes after the comma is a note.
        $parts = explode(',', $text, 2);
        $name = trim($parts[0]);
        $note = isset($parts[1]) && trim($parts[1]) !== '' ? trim($parts[1]) : null;

        if ($name === '') {
            return [];
        }

        // "Salt och peppar" is two groceries when there is no amount.
        if ($quantity === null && preg_match('/^(\S+(?:\s\S+)?) och (\S+(?:\s\S+)?)$/u', $name, $both) === 1) {
            return [
                ['name' => self::capital($both[1]), 'quantity' => null, 'unit' => null, 'note' => $note],
                ['name' => self::capital($both[2]), 'quantity' => null, 'unit' => null, 'note' => $note],
            ];
        }

        return [['name' => self::capital($name), 'quantity' => $quantity === null ? null : round($quantity, 3), 'unit' => $unit, 'note' => $note]];
    }

    /**
     * The amount first in the line: "1", "1,5", "½", "1 ½", "1 1/2", "2-3" (the first).
     *
     * @return array{0: float|null, 1: string}
     */
    private static function quantity(string $text): array
    {
        $fractions = implode('', array_keys(self::FRACTIONS));
        $pattern = '/^(?:ca\.?\s+|cirka\s+)?'
            .'(?:(?<onum>\d+)\/(?<oden>\d+)'                                      // 1/2
            .'|(?<whole>\d+(?:[.,]\d+)?)(?:\s*(?<uni>['.$fractions.'])|\s+(?<num>\d+)\/(?<den>\d+))?' // 1, 1,5, 1½, 1 1/2
            .'|(?<alone>['.$fractions.']))'                                         // ½
            .'(?:\s*[-–]\s*\d+(?:[.,]\d+)?)?\s*/u';                             // 2-3

        if (preg_match($pattern, $text, $match) !== 1) {
            return [null, $text];
        }

        $fraction = fn (string $num, string $den): float => (int) $den > 0 ? (int) $num / (int) $den : 0.0;

        $quantity = match (true) {
            ($match['onum'] ?? '') !== '' => $fraction($match['onum'], $match['oden'] ?? '0'),
            ($match['alone'] ?? '') !== '' => self::FRACTIONS[$match['alone']],
            default => (float) str_replace(',', '.', $match['whole'] ?? '0')
                + (($match['uni'] ?? '') !== '' ? self::FRACTIONS[$match['uni']] : 0.0)
                + (($match['num'] ?? '') !== '' ? $fraction($match['num'], $match['den'] ?? '0') : 0.0),
        };

        return $quantity > 0 ? [$quantity, trim(substr($text, strlen($match[0])))] : [null, $text];
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private static function unit(string $text): array
    {
        if (preg_match('/^(\S+)\s*(.*)$/u', $text, $match) !== 1) {
            return [null, $text];
        }

        $word = mb_strtolower(rtrim($match[1], '.'));

        return isset(self::UNITS[$word]) && $match[2] !== ''
            ? [self::UNITS[$word], $match[2]]
            : [null, $text];
    }

    private static function capital(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
