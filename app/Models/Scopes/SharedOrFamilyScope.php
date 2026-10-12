<?php

namespace App\Models\Scopes;

use App\Support\CurrentFamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Shared rows (family_id null, kept up in the admin portal) and the current
 * family's own: the picture gallery and the family's photos, the basic list
 * of ingredients and the family's own. Without a current family (e.g. in the
 * admin portal) only the shared rows.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class SharedOrFamilyScope implements Scope
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
