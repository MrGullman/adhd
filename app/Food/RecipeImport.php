<?php

namespace App\Food;

use App\Models\Ingredient;
use App\Support\PublicHttp;
use DateInterval;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * A recipe from a link. Most recipe sites describe the recipe for search
 * engines (schema.org/Recipe in JSON-LD); that is read, never the page's
 * layout. The result is a draft that a parent checks before saving. The
 * site's photo is not copied.
 */
class RecipeImport
{
    private const MAX_BYTES = 3_000_000;

    /**
     * @return array{name: string, portions: int, timeMinutes: int|null, instructions: string|null, sourceUrl: string, ingredients: list<array{ingredientId: int|null, name: string, quantity: float|null, unit: string|null, note: string|null}>}
     *
     * @throws RuntimeException When there is no recipe at the address.
     */
    public function fromUrl(string $url): array
    {
        return $this->fromHtml(PublicHttp::get($url, self::MAX_BYTES, timeout: 15), $url);
    }

    /**
     * @return array{name: string, portions: int, timeMinutes: int|null, instructions: string|null, sourceUrl: string, ingredients: list<array{ingredientId: int|null, name: string, quantity: float|null, unit: string|null, note: string|null}>}
     */
    public function fromHtml(string $html, string $url): array
    {
        $recipe = $this->findRecipe($html) ?? throw new RuntimeException('Hittade inget recept på sidan.');

        $lines = [];

        foreach ((array) ($recipe['recipeIngredient'] ?? $recipe['ingredients'] ?? []) as $text) {
            if (is_string($text)) {
                array_push($lines, ...IngredientLine::parse($text));
            }
        }

        if ($lines === [] && ! is_string($recipe['name'] ?? null)) {
            throw new RuntimeException('Hittade inget recept på sidan.');
        }

        return [
            'name' => Str::limit($this->text($recipe['name'] ?? 'Recept'), 100, ''),
            'portions' => $this->portions($recipe['recipeYield'] ?? null),
            'timeMinutes' => $this->minutes($recipe['totalTime'] ?? null) ?? $this->sum($this->minutes($recipe['prepTime'] ?? null), $this->minutes($recipe['cookTime'] ?? null)),
            'instructions' => $this->instructions($recipe['recipeInstructions'] ?? null),
            'sourceUrl' => $url,
            'ingredients' => array_slice(array_map(fn (array $line) => [
                'ingredientId' => $this->match($line['name']),
                'name' => Str::limit($line['name'], 150, ''),
                'quantity' => $line['quantity'],
                'unit' => $line['unit'],
                'note' => $line['note'] === null ? null : Str::limit($line['note'], 100, ''),
            ], $lines), 0, 50),
        ];
    }

    /**
     * The first schema.org Recipe in the page's JSON-LD, also inside @graph or lists.
     *
     * @return array<string, mixed>|null
     */
    private function findRecipe(string $html): ?array
    {
        preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $blocks);

        foreach ($blocks[1] as $json) {
            try {
                $data = json_decode(trim($json), true, 64, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                continue;
            }

            $found = $this->recipeIn($data);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recipeIn(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }

        $type = $data['@type'] ?? null;

        if ($type === 'Recipe' || (is_array($type) && in_array('Recipe', $type, true))) {
            return $data;
        }

        foreach ([...(array) ($data['@graph'] ?? []), ...array_is_list($data) ? $data : []] as $item) {
            $found = $this->recipeIn($item);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function portions(mixed $yield): int
    {
        foreach ((array) $yield as $value) {
            if ((is_string($value) || is_numeric($value)) && preg_match('/\d+/', (string) $value, $match) === 1) {
                return max(1, min(20, (int) $match[0]));
            }
        }

        return 4;
    }

    /**
     * "PT1H30M" as 90.
     */
    private function minutes(mixed $duration): ?int
    {
        if (! is_string($duration) || $duration === '') {
            return null;
        }

        try {
            $interval = new DateInterval($duration);
        } catch (Throwable) {
            return null;
        }

        $minutes = $interval->d * 1440 + $interval->h * 60 + $interval->i;

        return $minutes > 0 ? min(600, $minutes) : null;
    }

    private function sum(?int $a, ?int $b): ?int
    {
        return $a === null && $b === null ? null : min(600, (int) $a + (int) $b);
    }

    /**
     * Steps as numbered lines; sections keep their heading.
     */
    private function instructions(mixed $instructions): ?string
    {
        $steps = [];

        $collect = function (mixed $item) use (&$collect, &$steps): void {
            if (is_string($item)) {
                // One text with line breaks is several steps.
                $broken = (string) preg_replace('#<br\s*/?>|</p>|</li>#i', "\n", $item);

                foreach (preg_split('/\R+/u', $broken) ?: [] as $line) {
                    $line = $this->text($line);

                    if ($line !== '') {
                        $steps[] = $line;
                    }
                }
            } elseif (is_array($item)) {
                if (($item['@type'] ?? null) === 'HowToSection') {
                    if (is_string($item['name'] ?? null)) {
                        $steps[] = $this->text($item['name']).':';
                    }

                    $collect($item['itemListElement'] ?? []);
                } elseif (isset($item['text']) || isset($item['name'])) {
                    $collect($item['text'] ?? $item['name']);
                } else {
                    foreach ($item as $child) {
                        $collect($child);
                    }
                }
            }
        };

        $collect($instructions);

        if ($steps === []) {
            return null;
        }

        $number = 0;
        $lines = [];

        foreach ($steps as $step) {
            $lines[] = str_ends_with($step, ':') ? $step : (++$number).'. '.$step;
        }

        return Str::limit(implode("\n", $lines), 5000, '');
    }

    /**
     * A grocery the family already knows by the same name, if any.
     */
    private function match(string $name): ?int
    {
        $first = IngredientText::parse($name)['names'][0] ?? null;

        if ($first === null) {
            return null;
        }

        $id = Ingredient::query()->whereRaw('lower(name) = ?', [mb_strtolower($first)])->value('id');

        return $id === null ? null : (int) $id;
    }

    private function text(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(is_scalar($value) ? (string) $value : ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
