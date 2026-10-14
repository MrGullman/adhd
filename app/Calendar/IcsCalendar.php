<?php

namespace App\Calendar;

use App\Support\LocalTime;
use App\Support\PublicHttp;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Str;
use RuntimeException;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Property;
use Sabre\VObject\Property\ICalendar\DateTime as DateTimeProperty;
use Sabre\VObject\Property\ICalendar\Duration;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Reads a calendar from its iCal link (Google, iCloud, Outlook) and lists
 * every occurrence in a window, recurring events included. Only scheduled
 * jobs call this, never a page (docs/ARCHITECTURE.md).
 */
class IcsCalendar
{
    /** Largest calendar file read, in bytes. */
    private const MAX_BYTES = 5_000_000;

    /**
     * @return list<array{uid: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable|null, all_day: bool, title: string, location: string|null}>
     */
    public function events(string $link, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return $this->parse($this->fetch($link), $from, $to);
    }

    public function fetch(string $link): string
    {
        $url = IcsLink::normalize($link);

        if (! IcsLink::isAllowed($url)) {
            throw new CalendarSyncException('Länken går inte att hämta.');
        }

        try {
            $body = PublicHttp::get($url, self::MAX_BYTES);
        } catch (RuntimeException $exception) {
            throw new CalendarSyncException('Kalendern svarade inte.', previous: $exception);
        }

        if (! str_contains($body, 'BEGIN:VCALENDAR')) {
            throw new CalendarSyncException('Länken gav ingen kalender.');
        }

        return $body;
    }

    /**
     * @return list<array{uid: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable|null, all_day: bool, title: string, location: string|null}>
     */
    public function parse(string $ics, CarbonImmutable $from, CarbonImmutable $to): array
    {
        try {
            $calendar = Reader::read($ics, Reader::OPTION_FORGIVING | Reader::OPTION_IGNORE_INVALID_LINES);

            if (! $calendar instanceof VCalendar) {
                throw new CalendarSyncException('Länken gav ingen kalender.');
            }

            // Recurring events become one event per occurrence, in UTC.
            $expanded = $calendar->expand($from, $to, new DateTimeZone(LocalTime::ZONE));
        } catch (CalendarSyncException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CalendarSyncException('Kalendern gick inte att läsa.', previous: $exception);
        }

        $events = [];

        foreach ($expanded->select('VEVENT') as $event) {
            $startProperty = $this->property($event, 'DTSTART');

            if (! $event instanceof VEvent || ! $startProperty instanceof DateTimeProperty
                || strtoupper($this->text($event, 'STATUS')) === 'CANCELLED') {
                continue;
            }

            $allDay = ! $startProperty->hasTime();
            $start = $this->moment($startProperty->getDateTime(new DateTimeZone(LocalTime::ZONE)), (string) $startProperty->getValue(), $allDay);
            $endProperty = $this->property($event, 'DTEND');
            $end = $endProperty instanceof DateTimeProperty
                ? $this->moment($endProperty->getDateTime(new DateTimeZone(LocalTime::ZONE)), (string) $endProperty->getValue(), $allDay)
                : null;
            $duration = $this->property($event, 'DURATION');

            if ($end === null && $duration instanceof Duration) {
                $end = $start->add($duration->getDateInterval());
            }

            if ($allDay && ($end === null || $end->lte($start))) {
                $end = $start->addDay();
            }

            $title = $this->text($event, 'SUMMARY');
            $location = $this->text($event, 'LOCATION');
            $uid = $this->text($event, 'UID');

            $events[] = [
                'uid' => Str::limit($uid !== '' ? $uid : md5($title.$start->toIso8601String()), 255, ''),
                'starts_at' => $start,
                'ends_at' => $end,
                'all_day' => $allDay,
                'title' => Str::limit($title === '' ? 'Upptagen' : $title, 255, ''),
                'location' => $location === '' ? null : Str::limit($location, 255, ''),
            ];
        }

        return $events;
    }

    /**
     * @param  Component<mixed, mixed>  $event
     * @return Property<mixed, mixed>|null
     */
    private function property(Component $event, string $name): ?Property
    {
        $found = $event->select($name);

        return $found === [] ? null : reset($found);
    }

    /**
     * @param  Component<mixed, mixed>  $event
     */
    private function text(Component $event, string $name): string
    {
        return trim((string) $this->property($event, $name)?->getValue());
    }

    /**
     * A time as UTC; an all-day date (e.g. "20261013") as that date at midnight.
     */
    private function moment(?DateTimeInterface $time, string $raw, bool $allDay): CarbonImmutable
    {
        if ($allDay || $time === null) {
            return CarbonImmutable::createFromFormat('Ymd', substr($raw, 0, 8), 'UTC')->startOfDay();
        }

        return CarbonImmutable::instance($time)->setTimezone('UTC');
    }
}
