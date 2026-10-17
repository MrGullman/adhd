<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\BelongsToFamily;
use Carbon\CarbonInterface;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Activities and events: simskola, someone away, who picks up, a different
 * day. Once (date to end_date) or every week on a weekday.
 *
 * @property int $id
 * @property int $family_id
 * @property ActivityType $type
 * @property string $name
 * @property int|null $picture_id
 * @property Carbon|null $date
 * @property Carbon|null $end_date
 * @property int|null $weekday
 * @property string|null $time
 * @property list<int>|null $reminders Minutes before, e.g. [120, 60].
 * @property bool $visible_to_children
 * @property bool $unusual
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'picture_id', 'date', 'end_date', 'weekday', 'time', 'reminders', 'visible_to_children', 'unusual'])]
class Activity extends Model
{
    /**
     * Reminders that can be chosen before an activity with a time, in minutes.
     */
    public const REMINDER_MINUTES = [15, 30, 60, 120];

    /** @use HasFactory<ActivityFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'weekday' => 'integer',
            'reminders' => 'array',
            'visible_to_children' => 'boolean',
            'unusual' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    /**
     * Who it concerns: the children who go, the adult who is away or picks up.
     *
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'activity_participants');
    }

    /**
     * The packing list, used again every time.
     *
     * @return HasMany<ActivityItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ActivityItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isWeekly(): bool
    {
        return $this->weekday !== null;
    }

    /**
     * Whether it happens on a calendar day in Sweden.
     */
    public function occursOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();
        $from = $this->date?->toDateString();
        $to = $this->end_date?->toDateString();

        if ($this->isWeekly()) {
            return $date->dayOfWeekIso === $this->weekday
                && ($from === null || $day >= $from)
                && ($to === null || $day <= $to);
        }

        return $from !== null && $day >= $from && $day <= ($to ?? $from);
    }

    /**
     * Whether a child sees it: what they take part in, or what is marked
     * "Visa för barnen" (e.g. who picks up). Never adults' other events.
     */
    public function isVisibleTo(User $child): bool
    {
        return $this->visible_to_children
            || $this->participants->contains(fn (User $participant) => $participant->is($child));
    }

    /**
     * Time as HH:MM, e.g. "17:15".
     */
    public function shortTime(): ?string
    {
        return $this->time === null ? null : substr($this->time, 0, 5);
    }
}
