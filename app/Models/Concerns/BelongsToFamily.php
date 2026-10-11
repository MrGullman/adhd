<?php

namespace App\Models\Concerns;

use App\Models\Family;
use App\Models\Scopes\FamilyScope;
use App\Support\CurrentFamily;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Use on every model with a family_id column (see docs/ARCHITECTURE.md).
 *
 * @property int $family_id
 */
trait BelongsToFamily
{
    public static function bootBelongsToFamily(): void
    {
        static::addGlobalScope(new FamilyScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('family_id') === null) {
                $model->setAttribute('family_id', app(CurrentFamily::class)->id());
            }
        });
    }

    /**
     * @return BelongsTo<Family, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
