<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One occurrence of an event in a family calendar. Times are UTC; an all-day
 * event's starts_at and ends_at are its dates (ends_at the day after).
 *
 * @property int $id
 * @property int $family_id
 * @property int $calendar_id
 * @property string $uid
 * @property Carbon $starts_at
 * @property Carbon|null $ends_at
 * @property bool $all_day
 * @property string $title
 * @property string|null $location
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['calendar_id', 'uid', 'starts_at', 'ends_at', 'all_day', 'title', 'location'])]
class CalendarEvent extends Model
{
    /** @use HasFactory<CalendarEventFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'all_day' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Calendar, $this>
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * Whether the event happens on a day in Sweden.
     */
    public function occursOn(CarbonImmutable $day): bool
    {
        $date = $day->toDateString();

        if ($this->all_day) {
            $last = ($this->ends_at ?? $this->starts_at->copy()->addDay())->copy()->subDay()->toDateString();

            return $this->starts_at->toDateString() <= $date && $date <= $last;
        }

        $start = $this->starts_at->copy()->setTimezone(LocalTime::ZONE);
        $end = ($this->ends_at ?? $this->starts_at)->copy()->setTimezone(LocalTime::ZONE);

        // An event that ends exactly at midnight does not reach the next day.
        if ($end->gt($start) && $end->format('H:i:s') === '00:00:00') {
            $end = $end->subSecond();
        }

        return $start->toDateString() <= $date && $date <= $end->toDateString();
    }

    /**
     * Start time in Sweden, e.g. "07:45", for the day it starts. Null for all-day
     * events and on the following days of an event over several days.
     */
    public function timeOn(CarbonImmutable $day): ?string
    {
        if ($this->all_day) {
            return null;
        }

        $start = $this->starts_at->copy()->setTimezone(LocalTime::ZONE);

        return $start->toDateString() === $day->toDateString() ? $start->format('H:i') : null;
    }

    public function endTime(): ?string
    {
        return $this->all_day || $this->ends_at === null ? null : $this->ends_at->copy()->setTimezone(LocalTime::ZONE)->format('H:i');
    }
}
