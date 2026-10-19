<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * "Inställd den här gången": one day a weekly activity does not happen, e.g.
 * simskolan the week the pool is closed. It is shown as Inställd, and there is
 * no packing or reminder that day.
 *
 * @property int $id
 * @property int $family_id
 * @property int $activity_id
 * @property Carbon $date
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['activity_id', 'date', 'created_by'])]
class ActivityCancellation extends Model
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
            'date' => 'date:Y-m-d',
        ];
    }
}
