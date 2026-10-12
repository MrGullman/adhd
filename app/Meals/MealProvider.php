<?php

namespace App\Meals;

use Carbon\CarbonImmutable;

/**
 * Where school meals come from. Matilda's public menu now; OpenMeal 3.0 can
 * be added later without changing the rest (docs/ARCHITECTURE.md).
 */
interface MealProvider
{
    public function name(): string;

    /**
     * The meals between two days.
     *
     * @return list<array{date: string, name: string, holiday: string|null, courses: list<array{name: string, option: string|null}>}>
     *
     * @throws MealProviderException When the provider does not answer as expected.
     */
    public function fetch(string $externalId, CarbonImmutable $from, CarbonImmutable $to): array;
}
