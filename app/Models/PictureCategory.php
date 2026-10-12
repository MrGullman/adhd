<?php

namespace App\Models;

use Database\Factories\PictureCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A gallery category, e.g. Morgon, Mat, Skola, Hygien or Känslor.
 * Not family data: kept up in the admin portal.
 *
 * @property int $id
 * @property string $name
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'sort_order'])]
class PictureCategory extends Model
{
    /** @use HasFactory<PictureCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Picture, $this>
     */
    public function pictures(): HasMany
    {
        return $this->hasMany(Picture::class);
    }
}
