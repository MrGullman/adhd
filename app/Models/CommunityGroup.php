<?php

namespace App\Models;

use Database\Factories\CommunityGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A Community group, e.g. "Skola och förskola". Shared by all families and
 * kept up by admins.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'sort_order'])]
class CommunityGroup extends Model
{
    /** @use HasFactory<CommunityGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_group_members')->withTimestamps();
    }

    /**
     * @return HasMany<CommunityThread, $this>
     */
    public function threads(): HasMany
    {
        return $this->hasMany(CommunityThread::class);
    }
}
