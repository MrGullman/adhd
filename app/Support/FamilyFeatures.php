<?php

namespace App\Support;

use App\Models\Family;

/**
 * Features an admin turns on for one family at a time in the admin portal,
 * e.g. the ICA connection while it is tried out. Off unless turned on.
 * Saved in families.settings.features.
 */
class FamilyFeatures
{
    /** The shopping list to ICA's (unofficial API, docs/ARCHITECTURE.md). */
    public const ICA = 'ica';

    public static function enabled(Family $family, string $feature): bool
    {
        return (bool) ($family->settings['features'][$feature] ?? false);
    }

    public static function set(Family $family, string $feature, bool $on): void
    {
        $settings = $family->settings ?? [];
        $settings['features'][$feature] = $on;
        $family->forceFill(['settings' => $settings])->save();
    }
}
