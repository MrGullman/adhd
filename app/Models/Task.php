<?php

namespace App\Models;

use App\Enums\DayPart;
use App\Models\Concerns\BelongsToFamily;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A task for one day. It can have a part of the day, a time or both; with a
 * time the part of the day follows from the family's day parts.
 *
 * date and time are the day and wall-clock time in Sweden, not instants.
 *
 * @property int $id
 * @property int $family_id
 * @property string $title
 * @property int|null $assigned_to
 * @property Carbon $date
 * @property DayPart|null $day_part
 * @property string|null $time
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'assigned_to', 'date', 'day_part', 'time', 'created_by'])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use BelongsToFamily, HasFactory;

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
