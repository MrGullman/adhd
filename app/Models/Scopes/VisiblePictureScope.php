<?php

namespace App\Models\Scopes;

use App\Support\CurrentFamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Pictures a family may see: the shared gallery and the family's own photos.
 * Without a current family (e.g. in the admin portal) only the gallery.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class VisiblePictureScope implements Scope
{
    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $familyId = app(CurrentFamily::class)->id();
        $column = $model->qualifyColumn('family_id');

        $builder->where(fn (Builder $query) => $familyId === null
            ? $query->whereNull($column)
            : $query->whereNull($column)->orWhere($column, $familyId));
    }
}
