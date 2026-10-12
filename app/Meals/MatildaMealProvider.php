<?php

namespace App\Meals;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Matilda Menu's public menu page. The endpoint is not officially documented
 * and may change, so everything goes through MealProvider.
 */
class MatildaMealProvider implements MealProvider
{
    public const URL = 'https://menu.matildaplatform.com/api/menu';

    public function name(): string
    {
        return 'matilda';
    }

    public function fetch(string $externalId, CarbonImmutable $from, CarbonImmutable $to): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->retry(2, 1000)
                ->acceptJson()
                ->get(self::URL, [
                    'distributorId' => $externalId,
                    'startDate' => $from->toDateString(),
                    'endDate' => $to->toDateString(),
                    'lang' => 'sv',
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new MealProviderException('Matilda svarade inte.', previous: $exception);
        }

        $meals = $response->json('meals');

        if (! is_array($meals)) {
            throw new MealProviderException('Matilda skickade inget menysvar.');
        }

        $result = [];

        foreach ($meals as $meal) {
            if (! is_array($meal) || ! is_string($meal['date'] ?? null)) {
                continue;
            }

            $courses = [];

            foreach ((array) ($meal['courses'] ?? []) as $course) {
                if (! is_array($course)) {
                    continue;
                }

                // Matilda sometimes sends names with trailing spaces.
                $name = trim((string) ($course['name'] ?? ''));
                $option = is_string($course['optionName'] ?? null) ? trim($course['optionName']) : '';

                if ($name !== '') {
                    $courses[] = ['name' => $name, 'option' => $option === '' ? null : $option];
                }
            }

            $holiday = is_string($meal['holidayName'] ?? null) ? trim($meal['holidayName']) : '';

            $result[] = [
                'date' => substr($meal['date'], 0, 10),
                'name' => Str::limit(trim((string) ($meal['name'] ?? 'Lunch')) ?: 'Lunch', 50, ''),
                'holiday' => $holiday === '' ? null : $holiday,
                'courses' => $courses,
            ];
        }

        return $result;
    }

    /**
     * The school from a Matilda link, e.g.
     * https://menu.matildaplatform.com/meals/week/{distributorId}_{namn}.
     *
     * @return array{id: string, name: string|null}|null
     */
    public static function parseLink(string $link): ?array
    {
        $path = (string) (parse_url(trim($link), PHP_URL_PATH) ?? '');
        $host = (string) (parse_url(trim($link), PHP_URL_HOST) ?? '');

        if (! str_ends_with($host, 'matildaplatform.com')
            || preg_match('#/meals/(?:week|day|month)/([0-9a-f]{24})(?:_([^/?]+))?#i', $path, $match) !== 1) {
            return null;
        }

        $slug = isset($match[2]) ? urldecode($match[2]) : null;
        $name = $slug === null ? null : Str::ucfirst(trim((string) preg_replace('/[-_]+/', ' ', $slug)));

        return ['id' => Str::lower($match[1]), 'name' => $name ?: null];
    }
}
