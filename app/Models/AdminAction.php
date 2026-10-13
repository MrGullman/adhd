<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something an admin did to an account or a family, for the log.
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string $action suspended or unsuspended
 * @property string $subject_type user or family
 * @property int $subject_id
 * @property string $subject_name
 * @property string|null $reason
 * @property Carbon|null $created_at
 */
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
