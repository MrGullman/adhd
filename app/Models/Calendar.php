<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\CalendarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property bool $visible_to_children
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $last_failed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'name', 'url', 'visible_to_children'])]
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
            'visible_to_children' => 'boolean',
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
     * Whether a child at home may see the events: the child's own calendar,
     * or one an adult chose to show the children.
     *
     * @param  list<int>  $childIds
     */
    public function isVisibleToChildren(array $childIds): bool
    {
        return $this->visible_to_children || ($this->user_id !== null && in_array($this->user_id, $childIds, true));
    }
}
