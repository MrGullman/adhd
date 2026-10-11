<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * The family's clock. The database stores timestamps in UTC, but "today",
 * scheduled jobs and everything shown use Swedish time (docs/ARCHITECTURE.md).
 */
class LocalTime
{
    public const ZONE = 'Europe/Stockholm';

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::ZONE);
    }

    /**
     * Today's date in Sweden, e.g. "2026-10-13".
     */
    public static function today(): string
    {
        return self::now()->toDateString();
    }
}
