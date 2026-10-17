<?php

namespace App\Models;

use App\Enums\DayPart;
use App\Enums\Frequency;
use App\Models\Concerns\BelongsToFamily;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\RecurringTaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A task that comes back, e.g. "Borsta tänderna" every morning. Each day it
 * occurs becomes a Task, created ahead by GenerateRecurringTasks.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $picture_id
 * @property string $title
 * @property int|null $assigned_to
 * @property DayPart|null $day_part
 * @property string|null $time
 * @property int|null $timer_minutes A visual timer the child starts, e.g. 20 for Äta.
 * @property Frequency $frequency
 * @property list<int>|null $weekdays
 * @property int $week_interval
 * @property Carbon $starts_on
 * @property Carbon|null $ends_on
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'picture_id', 'assigned_to', 'day_part', 'time', 'timer_minutes', 'frequency', 'weekdays', 'week_interval', 'starts_on', 'ends_on', 'created_by'])]
class RecurringTask extends Model
{
    /** @use HasFactory<RecurringTaskFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_part' => DayPart::class,
            'frequency' => Frequency::class,
            'weekdays' => 'array',
            'week_interval' => 'integer',
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Bildstöd.
     *
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Whether the task occurs on a calendar day in Sweden.
     */
    public function occursOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        if ($day < $this->starts_on->toDateString()) {
            return false;
        }

        if ($this->ends_on !== null && $day > $this->ends_on->toDateString()) {
            return false;
        }

        if ($this->frequency === Frequency::Daily) {
            return true;
        }

        if (! in_array($date->dayOfWeekIso, $this->weekdays ?? [], true)) {
            return false;
        }

        // Every other week counts whole weeks from the week the task starts.
        // Both days are compared as plain calendar days, so time zones and summer time do not count.
        $startWeek = CarbonImmutable::parse($this->starts_on->toDateString(), 'UTC')->startOfWeek(CarbonInterface::MONDAY);
        $week = CarbonImmutable::parse($day, 'UTC')->startOfWeek(CarbonInterface::MONDAY);
        $weeks = intdiv((int) $startWeek->diffInDays($week), 7);

        return $weeks % max(1, $this->week_interval) === 0;
    }

    /**
     * The time as HH:MM, e.g. "07:45".
     */
    public function shortTime(): ?string
    {
        return $this->time === null ? null : substr($this->time, 0, 5);
    }
}
