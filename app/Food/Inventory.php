<?php

namespace App\Food;

use App\Enums\StorageLocation;
use App\Models\Family;

/**
 * When each place at home was last gone through (families.settings).
 */
class Inventory
{
    /**
     * @return array<string, string> ISO time per location, e.g. ['kyl' => '2026-10-14T08:00:00+00:00']
     */
    public static function lastTaken(Family $family): array
    {
        $saved = (array) ($family->settings['inventoried'] ?? []);

        return array_filter($saved, fn ($value, $key) => is_string($value) && StorageLocation::tryFrom((string) $key) !== null, ARRAY_FILTER_USE_BOTH);
    }

    public static function markTaken(Family $family, StorageLocation $location): void
    {
        $settings = $family->settings ?? [];
        $settings['inventoried'] = [...self::lastTaken($family), $location->value => now()->toIso8601String()];

        $family->forceFill(['settings' => $settings])->save();
    }
}
