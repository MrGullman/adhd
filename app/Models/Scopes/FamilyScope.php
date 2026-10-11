<?php

namespace App\Models\Scopes;

use App\Support\CurrentFamily;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limits every query to the current family. Without a current family no rows
 * are returned, so data can never leak between families by mistake.
 *
 * @template TModel of Model
 *
 * @implements Scope<TModel>
 */
class FamilyScope implements Scope
{
    /**
     * @param  Builder<covariant TModel>  $builder
     * @param  TModel  $model
     */
    public function apply(Builder $builder, Model $model): void
    {
        $familyId = app(CurrentFamily::class)->id();

        if ($familyId === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->where($model->qualifyColumn('family_id'), $familyId);
    }
}
