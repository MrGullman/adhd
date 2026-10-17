<?php

namespace App\Food;

/**
 * A recipe's instructions as steps for the cooking view, one at a time. A
 * time in a step, e.g. "Koka i 10–12 minuter", becomes a timer, so nothing
 * extra has to be filled in. A line ending with ":" is a heading for the
 * steps after it, e.g. "Såsen:".
 */
class CookingSteps
{
    /** A timer is never longer than this (5 hours). */
    private const MAX_SECONDS = 18_000;

    /** @var array<string, int> */
    private const WORDS = ['en kvart' => 900, 'en halvtimme' => 1800, 'en halv timme' => 1800, 'en timme' => 3600];

    /**
     * @return list<array{number: int, text: string, section: string|null, timers: list<array{seconds: int, label: string}>}>
     */
    public static function from(?string $instructions): array
    {
        $steps = [];
        $section = null;

        foreach (preg_split('/\R/u', (string) $instructions) ?: [] as $line) {
            // "1. Koka pastan" or "2) Koka pastan": the number is shown by the view.
            $text = trim((string) preg_replace('/^\s*(\d+\s*[.)]|[-•*])\s*/u', '', $line));

            if ($text === '') {
                continue;
            }

            if (str_ends_with($text, ':')) {
                $section = rtrim($text, ':');

                continue;
            }

            $steps[] = [
                'number' => count($steps) + 1,
                'text' => $text,
                'section' => $section,
                'timers' => self::timers($text),
            ];
        }

        return $steps;
    }

    /**
     * Which ingredients a step uses: those whose name starts a word in the
     * step, also with an ending ("pasta" in "Koka pastan"). A name inside a
     * longer one that matched is not counted, so "lök" is not found in "vitlök"
     * and "mjöl" not in "vetemjöl" (the word must start with the name).
     *
     * @param  array<int, list<string>>  $ingredients  Names per ingredient line id, e.g. [3 => ['Smör', 'margarin']].
     * @return list<int> Ingredient line ids, in the order they come in the step.
     */
    public static function ingredientsIn(string $text, array $ingredients): array
    {
        $lower = mb_strtolower($text);
        $matches = [];

        foreach ($ingredients as $id => $names) {
            foreach (self::candidates($names) as $name) {

                if (preg_match('/(?<![\p{L}])'.preg_quote($name, '/').'/u', $lower, $match, PREG_OFFSET_CAPTURE) === 1) {
                    $at = $match[0][1];
                    $length = strlen($name);

                    if (! isset($matches[$id]) || $at < $matches[$id]['at']) {
                        $matches[$id] = ['at' => $at, 'end' => $at + $length];
                    }
                }
            }
        }

        // A shorter name inside a longer match ("ost" in "ostkaka" when both are ingredients) is dropped.
        $kept = array_filter($matches, function (array $match, int $id) use ($matches): bool {
            foreach ($matches as $other => $longer) {
                if ($other !== $id && $longer['at'] <= $match['at'] && $longer['end'] >= $match['end'] && ($longer['end'] - $longer['at']) > ($match['end'] - $match['at'])) {
                    return false;
                }
            }

            return true;
        }, ARRAY_FILTER_USE_BOTH);

        uasort($kept, fn (array $a, array $b) => $a['at'] <=> $b['at']);

        return array_keys($kept);
    }

    /**
     * The names to look for, lower case: each name, and for "Gul lök" or
     * "Krossade tomater" also the last word, since a step says "Hacka löken".
     *
     * @param  list<string>  $names
     * @return list<string>
     */
    private static function candidates(array $names): array
    {
        $candidates = [];

        foreach ($names as $name) {
            $name = mb_strtolower(trim($name));
            $words = preg_split('/\s+/u', $name) ?: [];
            $last = end($words);

            if (mb_strlen($name) >= 2) {
                $candidates[] = $name;
            }

            if (count($words) > 1 && is_string($last) && mb_strlen($last) >= 3) {
                $candidates[] = $last;
            }
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Times in a step, at most two, e.g. "ca 10–12 min" as 10 minutes: check early rather than late.
     *
     * @return list<array{seconds: int, label: string}>
     */
    public static function timers(string $text): array
    {
        $found = [];
        $lower = mb_strtolower($text);

        preg_match_all(
            '/(\d+(?:[.,]\d+)?)(?:\s*(?:-|–|till)\s*\d+(?:[.,]\d+)?)?\s*(timmar|timme|tim|h|minuter|minut|min|sekunder|sek|s)(?![\p{L}])/u',
            $lower,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        foreach ($matches as $match) {
            $amount = (float) str_replace(',', '.', $match[1][0]);
            $seconds = (int) round($amount * match ($match[2][0]) {
                'timmar', 'timme', 'tim', 'h' => 3600,
                'minuter', 'minut', 'min' => 60,
                default => 1,
            });
            $found[$match[0][1]] = $seconds;
        }

        foreach (self::WORDS as $words => $seconds) {
            $at = mb_strpos($lower, $words);

            if ($at !== false) {
                $found[strlen(mb_substr($lower, 0, $at))] = $seconds;
            }
        }

        ksort($found);
        $timers = [];

        foreach ($found as $seconds) {
            if ($seconds < 10 || $seconds > self::MAX_SECONDS || in_array($seconds, array_column($timers, 'seconds'), true)) {
                continue;
            }

            $timers[] = ['seconds' => $seconds, 'label' => self::label($seconds)];
        }

        return array_slice($timers, 0, 2);
    }

    /**
     * 600 as "10 min", 5400 as "1 h 30 min", 45 as "45 sek".
     */
    public static function label(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds} sek";
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $rest = $seconds % 60;

        return trim(($hours > 0 ? "{$hours} h " : '').($minutes > 0 ? "{$minutes} min " : '').($rest > 0 ? "{$rest} sek" : ''));
    }
}
