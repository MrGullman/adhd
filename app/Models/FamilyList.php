<?php

namespace App\Models;

use App\Enums\ListType;
use App\Models\Concerns\BelongsToFamily;
use Database\Factories\FamilyListFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A shared list, e.g. the shopping list. ("List" is a reserved word in PHP.)
 *
 * @property int $id
 * @property int $family_id
 * @property ListType $type
 * @property string $name
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('lists')]
#[Fillable(['type', 'name', 'created_by'])]
class FamilyList extends Model
{
    /** @use HasFactory<FamilyListFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ListType::class,
        ];
    }

    /**
     * @return HasMany<ListItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ListItem::class, 'list_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isShopping(): bool
    {
        return $this->type === ListType::Shopping;
    }
}
