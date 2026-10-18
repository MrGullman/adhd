<?php

namespace App\Models;

use App\Enums\DayPart;
use App\Models\Concerns\BelongsToFamily;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A task for one day. It can have a part of the day, a time or both; with a
 * time the part of the day follows from the family's day parts.
 *
 * date and time are the day and wall-clock time in Sweden, not instants.
 * Deleting an occurrence of a recurring task keeps it as a skipped day
 * (soft delete), so that it is not created again.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $recurring_task_id
 * @property int|null $picture_id
 * @property string $title
 * @property int|null $assigned_to
 * @property Carbon $date
 * @property DayPart|null $day_part
 * @property string|null $time
 * @property int|null $timer_minutes A visual timer the child starts, e.g. 20 for Äta.
 * @property bool $homework Läxa: date is when it is due, shown every day until then.
 * @property bool $customized
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['title', 'picture_id', 'assigned_to', 'date', 'day_part', 'time', 'timer_minutes', 'homework', 'created_by'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use BelongsToFamily, HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'day_part' => DayPart::class,
            'customized' => 'boolean',
            'homework' => 'boolean',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<RecurringTask, $this>
     */
    public function recurringTask(): BelongsTo
    {
        return $this->belongsTo(RecurringTask::class);
    }

    /**
     * A task a child added for themselves (läget Ungdom). It never gives points.
     */
    public function isOwnTask(): bool
    {
        return $this->created_by !== null && $this->created_by === $this->assigned_to;
    }

    public function isRecurring(): bool
    {
        return $this->recurring_task_id !== null;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * The time as HH:MM, e.g. "07:45".
     */
    public function shortTime(): ?string
    {
        return $this->time === null ? null : substr($this->time, 0, 5);
    }

    public function complete(User $user): void
    {
        $this->forceFill(['completed_at' => now(), 'completed_by' => $user->id])->save();
    }

    public function reopen(): void
    {
        $this->forceFill(['completed_at' => null, 'completed_by' => null])->save();
    }
}
