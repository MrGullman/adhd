<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Swedish special days, worked out in code without asking anyone: red days
 * and holidays for everyone, flag days only for adults.
 */
class SpecialDays
{
    /**
     * What is special about a day, e.g. ["Julafton"] or ["Röd dag: Juldagen", "Flaggdag"].
     * Children get the red days and holidays by name only.
     *
     * @return list<string>
     */
    public static function on(string $date, bool $forChildren = false): array
    {
        $day = CarbonImmutable::parse($date, LocalTime::ZONE)->startOfDay();
        $days = self::year($day->year)[$day->format('m-d')] ?? [];
        $result = [];

        foreach ($days as [$name, $kind]) {
            if ($kind === 'flag' && $forChildren) {
                continue;
            }

            $result[] = match (true) {
                $forChildren => $name,
                $kind === 'red' => "Röd dag: {$name}",
                $kind === 'flag' => "Flaggdag: {$name}",
                default => $name,
            };
        }

        // Most red days are also flag days: one line is enough.
        if (! $forChildren && self::isFlagDay($day) && ! array_filter($days, fn (array $entry) => $entry[1] === 'flag')) {
            $result[] = 'Flaggdag';
        }

        return $result;
    }

    /**
     * @return array<string, list<array{0: string, 1: 'red'|'holiday'|'flag'}>> By "MM-DD".
     */
    private static function year(int $year): array
    {
        $easter = self::easter($year);
        $midsummerEve = self::firstWeekdayFrom($year, 6, 19, CarbonImmutable::FRIDAY);
        $allSaints = self::firstWeekdayFrom($year, 10, 31, CarbonImmutable::SATURDAY);

        $entries = [
            // Röda dagar
            [CarbonImmutable::create($year, 1, 1), 'Nyårsdagen', 'red'],
            [CarbonImmutable::create($year, 1, 6), 'Trettondedag jul', 'red'],
            [$easter->subDays(2), 'Långfredagen', 'red'],
            [$easter, 'Påskdagen', 'red'],
            [$easter->addDay(), 'Annandag påsk', 'red'],
            [CarbonImmutable::create($year, 5, 1), 'Första maj', 'red'],
            [$easter->addDays(39), 'Kristi himmelsfärds dag', 'red'],
            [$easter->addDays(49), 'Pingstdagen', 'red'],
            [CarbonImmutable::create($year, 6, 6), 'Sveriges nationaldag', 'red'],
            [$midsummerEve->addDay(), 'Midsommardagen', 'red'],
            [$allSaints, 'Alla helgons dag', 'red'],
            [CarbonImmutable::create($year, 12, 25), 'Juldagen', 'red'],
            [CarbonImmutable::create($year, 12, 26), 'Annandag jul', 'red'],
            // Högtider och dagar som barnen känner igen
            [$easter->subDay(), 'Påskafton', 'holiday'],
            [CarbonImmutable::create($year, 4, 30), 'Valborg', 'holiday'],
            [self::lastSunday($year, 5), 'Mors dag', 'holiday'],
            [$midsummerEve, 'Midsommarafton', 'holiday'],
            [self::firstWeekdayFrom($year, 11, 8, CarbonImmutable::SUNDAY), 'Fars dag', 'holiday'],
            [CarbonImmutable::create($year, 12, 13), 'Lucia', 'holiday'],
            [CarbonImmutable::create($year, 12, 24), 'Julafton', 'holiday'],
            [CarbonImmutable::create($year, 12, 31), 'Nyårsafton', 'holiday'],
            // Allmänna flaggdagar som inte är röda dagar
            [CarbonImmutable::create($year, 1, 28), 'Konungens namnsdag', 'flag'],
            [CarbonImmutable::create($year, 3, 12), 'Kronprinsessans namnsdag', 'flag'],
            [CarbonImmutable::create($year, 4, 30), 'Konungens födelsedag', 'flag'],
            [CarbonImmutable::create($year, 7, 14), 'Kronprinsessans födelsedag', 'flag'],
            [CarbonImmutable::create($year, 8, 8), 'Drottningens namnsdag', 'flag'],
            [CarbonImmutable::create($year, 10, 24), 'FN-dagen', 'flag'],
            [CarbonImmutable::create($year, 11, 6), 'Gustav Adolfsdagen', 'flag'],
            [CarbonImmutable::create($year, 12, 10), 'Nobeldagen', 'flag'],
            [CarbonImmutable::create($year, 12, 23), 'Drottningens födelsedag', 'flag'],
        ];

        $byDay = [];

        foreach ($entries as [$date, $name, $kind]) {
            $byDay[$date->format('m-d')][] = [$name, $kind];
        }

        return $byDay;
    }

    /**
     * Red days that are also flag days: Nyårsdagen, Påskdagen, Första maj,
     * Pingstdagen, Nationaldagen, Midsommardagen and Juldagen.
     */
    private static function isFlagDay(CarbonImmutable $day): bool
    {
        $easter = self::easter($day->year);
        $midsummerDay = self::firstWeekdayFrom($day->year, 6, 19, CarbonImmutable::FRIDAY)->addDay();

        return in_array($day->format('m-d'), [
            '01-01', '05-01', '06-06', '12-25',
            $easter->format('m-d'),
            $easter->addDays(49)->format('m-d'),
            $midsummerDay->format('m-d'),
        ], true);
    }

    /**
     * Easter Sunday (Gregorian, the anonymous algorithm).
     */
    private static function easter(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return CarbonImmutable::create($year, $month, $day);
    }

    /**
     * The first given weekday on or after a date, e.g. Midsommarafton is the
     * Friday from 19 June.
     */
    private static function firstWeekdayFrom(int $year, int $month, int $day, int $weekday): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, $month, $day);

        return $date->dayOfWeek === $weekday ? $date : $date->next($weekday);
    }

    private static function lastSunday(int $year, int $month): CarbonImmutable
    {
        $last = CarbonImmutable::create($year, $month)->endOfMonth()->startOfDay();

        return $last->isSunday() ? $last : $last->previous(CarbonImmutable::SUNDAY);
    }
}
