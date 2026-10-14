<?php

namespace App\Models;

use App\Enums\StorageLocation;
use App\Enums\StoreSection;
use App\Models\Scopes\SharedOrFamilyScope;
use Database\Factories\IngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A grocery, from the shared basic list (family_id null) or the family's own.
 * Recipes and the pantry use the same ingredients, so they can be compared.
 *
 * Not BelongsToFamily: SharedOrFamilyScope shows the basic list and the
 * family's own. family_id is always set explicitly.
 *
 * @property int $id
 * @property int|null $family_id
 * @property string $name
 * @property int|null $picture_id
 * @property StoreSection $category
 * @property string|null $default_unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'picture_id', 'category', 'default_unit'])]
#[ScopedBy([SharedOrFamilyScope::class])]
class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => StoreSection::class,
        ];
    }

    /**
     * @return BelongsTo<Picture, $this>
     */
    public function picture(): BelongsTo
    {
        return $this->belongsTo(Picture::class);
    }

    /**
     * The ingredient with this name the family can use, or a new one of the
     * family's own. Names are matched without regard to case, so "mjölk" is
     * the basic list's "Mjölk".
     */
    public static function findOrCreateForFamily(string $name, Family $family): self
    {
        $name = trim($name);

        $existing = self::query()
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->orderByRaw('family_id is not null')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $ingredient = new self(['name' => mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1), 'category' => StoreSection::Other]);
        $ingredient->family_id = $family->id;
        $ingredient->save();

        return $ingredient;
    }

    /**
     * @return array{id: int, name: string, unit: string|null, location: string, picture: array{id: int, label: string, url: string}|null}
     */
    public function toOption(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->default_unit,
            'location' => StorageLocation::for($this->category)->value,
            'picture' => $this->picture?->toPicker(),
        ];
    }
}
