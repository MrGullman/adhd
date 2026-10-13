<?php

namespace App\Food;

/**
 * Reads what was typed for an ingredient: "smör el margarin (till stekning)"
 * is two groceries that will both do, Smör and Margarin, and the note "till
 * stekning". What is in brackets never becomes part of a grocery's name.
 */
class IngredientText
{
    /**
     * @return array{names: list<string>, note: string|null}
     */
    public static function parse(string $text): array
    {
        preg_match_all('/\(([^)]*)\)?/u', $text, $brackets);
        $notes = array_values(array_filter(array_map('trim', $brackets[1])));

        $rest = (string) preg_replace('/\([^)]*\)?/u', ' ', $text);
        $parts = preg_split('/\s+(?:el\.?|eller)\s+|\s*\/\s*/iu', $rest) ?: [];

        $names = [];

        foreach ($parts as $part) {
            $name = trim((string) preg_replace('/\s+/u', ' ', $part));

            if ($name !== '' && ! in_array(mb_strtolower($name), array_map('mb_strtolower', $names), true)) {
                $names[] = $name;
            }
        }

        return ['names' => $names, 'note' => $notes === [] ? null : implode(', ', $notes)];
    }
}
