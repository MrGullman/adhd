<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\CalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A calendar from Google, iPhone or Outlook, read through its iCal link.
 *
 * @property int $id
 * @property int $family_id
 * @property int|null $user_id
 * @property string $name
 * @property string $provider
 * @property string $url
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'name', 'url'])]
#[Hidden(['url'])]
class Calendar extends Model
{
    /** @use HasFactory<CalendarFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'url' => 'encrypted',
            'last_synced_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<CalendarEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * The children who see the calendar ("Visa för").
     *
     * @return BelongsToMany<User, $this>
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_child');
    }

    /**
     * Whether the calendar is shown to this child.
     */
    public function isShownTo(User $child): bool
    {
        return $this->children->contains($child);
    }

    /**
     * Whether any child sees the calendar: then the home display and the
     * other adults may show it too.
     */
    public function isShownToChildren(): bool
    {
        return $this->children->isNotEmpty();
    }
}
