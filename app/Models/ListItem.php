<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\ListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $family_id
 * @property int $list_id
 * @property int|null $picture_id
 * @property string $name
 * @property int|null $added_by
 * @property Carbon|null $completed_at
 * @property int|null $completed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'picture_id', 'added_by'])]
class ListItem extends Model
{
    /** @use HasFactory<ListItemFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<FamilyList, $this>
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(FamilyList::class, 'list_id');
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
     * @return BelongsTo<User, $this>
     */
    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
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
