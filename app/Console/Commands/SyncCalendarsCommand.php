<?php

namespace App\Console\Commands;

use App\Actions\Calendars\SyncCalendar;
use App\Models\Calendar;
use App\Models\Family;
use App\Support\CurrentFamily;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Runs every 15 minutes (routes/console.php). Each family is handled as the
 * current family, so the family scope applies as on any page.
 */
#[Signature('calendars:sync')]
#[Description('Läser alla familjers kalendrar från deras iCal-länkar')]
class SyncCalendarsCommand extends Command
{
    public function handle(SyncCalendar $sync, CurrentFamily $current): int
    {
        $synced = 0;
        $failed = 0;

        try {
            // Only families with a calendar; the calendars themselves are read per family.
            Family::query()->whereIn('id', DB::table('calendars')->select('family_id'))->lazyById()->each(function (Family $family) use ($sync, $current, &$synced, &$failed): void {
                $current->set($family);

                Calendar::query()->each(function (Calendar $calendar) use ($sync, &$synced, &$failed): void {
                    $sync->handle($calendar) ? $synced++ : $failed++;
                });
            });
        } finally {
            $current->forget();
        }

        $this->info("{$synced} kalendrar lästa, {$failed} misslyckades.");

        return self::SUCCESS;
    }
}
