<?php

namespace App\Jobs;

use App\Actions\Calendars\SyncCalendar;
use App\Models\Calendar;
use App\Models\Family;
use App\Support\CurrentFamily;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Reads a calendar in the background, so that no page waits for Google,
 * Apple or Microsoft.
 */
class SyncCalendarJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $uniqueFor = 600;

    public function __construct(public int $calendarId, public int $familyId) {}

    public function uniqueId(): string
    {
        return (string) $this->calendarId;
    }

    public function handle(SyncCalendar $sync, CurrentFamily $current): void
    {
        $family = Family::find($this->familyId);

        if ($family === null) {
            return;
        }

        $current->set($family);

        try {
            $calendar = Calendar::query()->find($this->calendarId);

            if ($calendar !== null) {
                $sync->handle($calendar);
            }
        } finally {
            $current->forget();
        }
    }
}
