<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\PointTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Points given (positive) or used (negative). A child's points are the sum.
 *
 * @property int $id
 * @property int $family_id
 * @property int $user_id
 * @property int $points
 * @property bool $pending
 * @property string $reason
 * @property string|null $source_type
 * @property int|null $source_id
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'points', 'pending', 'reason', 'source_type', 'source_id', 'created_by'])]
class PointTransaction extends Model
{
    /** @use HasFactory<PointTransactionFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'pending' => 'boolean',
        ];
    }

    /**
     * Only the points that count: not those waiting for a parent's Godkänn.
     *
     * @param  Builder<PointTransaction>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->where('pending', false);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function giver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
