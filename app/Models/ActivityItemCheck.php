<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A thing packed by a child for one time the activity happens.
 *
 * @property int $id
 * @property int $activity_id
 * @property int $activity_item_id
 * @property int $user_id
 * @property Carbon $date
 * @property Carbon $checked_at
 */
#[Fillable(['activity_id', 'activity_item_id', 'user_id', 'date', 'checked_at'])]
#[WithoutTimestamps]
class ActivityItemCheck extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'checked_at' => 'datetime',
        ];
    }
}
