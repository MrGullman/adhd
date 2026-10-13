<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\RewardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something in real life a child can wish for with points, e.g. "Välja
 * film". Never screen time in a game (docs/ARCHITECTURE.md).
 *
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property int|null $picture_id
 * @property int $cost
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'picture_id', 'cost', 'active'])]
class Reward extends Model
{
    /** @use HasFactory<RewardFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }
}
