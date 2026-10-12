<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFamily;
use Database\Factories\SchoolMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A school's menu at a meal provider (Matilda). Children are linked through
 * family_user.school_menu_id.
 *
 * @property int $id
 * @property int $family_id
 * @property string $name
 * @property string $provider
 * @property string $external_id
 * @property Carbon|null $fetched_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'provider', 'external_id'])]
class SchoolMenu extends Model
{
    /** @use HasFactory<SchoolMenuFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SchoolMeal, $this>
     */
    public function meals(): HasMany
    {
        return $this->hasMany(SchoolMeal::class);
    }
}
