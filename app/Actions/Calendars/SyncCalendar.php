<?php

namespace App\Actions\Calendars;

use App\Calendar\CalendarSyncException;
use App\Calendar\IcsCalendar;
use App\Models\Calendar;
use App\Support\LocalTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reads a calendar again: one week back and eight weeks ahead. When the
 * calendar does not answer, what was read before is kept.
 */
class SyncCalendar
{
    public function __construct(private IcsCalendar $ics) {}

    /**
     * @return bool Whether the calendar could be read.
     */
    public function handle(Calendar $calendar): bool
    {
        $from = LocalTime::now()->startOfDay()->subWeek();
        $to = LocalTime::now()->startOfDay()->addWeeks(8);

        try {
            $events = $this->ics->events($calendar->url, $from, $to);
        } catch (CalendarSyncException $exception) {
            Log::warning('Kalendern kunde inte hämtas.', ['calendar_id' => $calendar->id, 'error' => $exception->getMessage()]);
            $calendar->forceFill(['last_failed_at' => now()])->save();

            return false;
        }

        DB::transaction(function () use ($calendar, $events): void {
            $calendar->events()->delete();

            foreach (array_chunk($events, 500) as $chunk) {
                $now = now();

                $calendar->events()->insert(array_map(fn (array $event) => [
                    'family_id' => $calendar->family_id,
                    'calendar_id' => $calendar->id,
                    'uid' => $event['uid'],
                    'starts_at' => $event['starts_at']->format('Y-m-d H:i:s'),
                    'ends_at' => $event['ends_at']?->format('Y-m-d H:i:s'),
                    'all_day' => $event['all_day'],
                    'title' => $event['title'],
                    'location' => $event['location'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }

            $calendar->forceFill(['last_synced_at' => now(), 'last_failed_at' => null])->save();
        });

        return true;
    }
}
