<?php

namespace App\Models;

use App\Enums\StorageLocation;
use App\Models\Concerns\BelongsToFamily;
use Database\Factories\PantryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Something at home. Without a quantity it counts as enough.
 *
 * @property int $id
 * @property int $family_id
 * @property int $ingredient_id
 * @property string|null $quantity
 * @property string|null $unit
 * @property StorageLocation $location
 * @property bool $always_home
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ingredient_id', 'quantity', 'unit', 'location', 'always_home'])]
class PantryItem extends Model
{
    /** @use HasFactory<PantryItemFactory> */
    use BelongsToFamily, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'always_home' => 'boolean',
            'location' => StorageLocation::class,
        ];
    }

    /**
     * @return BelongsTo<Ingredient, $this>
     */
    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
