<?php

namespace App\Models;

use App\Enums\RedemptionStatus;
use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A child's wish for a reward, answered by a parent.
 *
 * @property int $id
 * @property int $family_id
 * @property int $reward_id
 * @property int $user_id
 * @property int $cost
 * @property RedemptionStatus $status
 * @property Carbon|null $decided_at
 * @property int|null $decided_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['reward_id', 'user_id', 'cost', 'status'])]
class RewardRedemption extends Model
{
    use BelongsToFamily;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'integer',
            'status' => RedemptionStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reward, $this>
     */
    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
