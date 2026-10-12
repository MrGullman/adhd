<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * "Ja, jag har packat": a child's answer on the day of the activity.
 *
 * @property int $id
 * @property int $activity_id
 * @property int $user_id
 * @property Carbon $date
 * @property Carbon $confirmed_at
 */
#[Fillable(['activity_id', 'user_id', 'date', 'confirmed_at'])]
#[WithoutTimestamps]
class ActivityConfirmation extends Model
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
            'confirmed_at' => 'datetime',
        ];
    }
}
