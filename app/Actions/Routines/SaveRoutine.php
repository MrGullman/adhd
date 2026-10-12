<?php

namespace App\Actions\Routines;

use App\Models\Routine;
use App\Models\RoutineStep;
use Illuminate\Support\Facades\DB;

/**
 * Saves a routine with its children and steps. Steps keep their id when
 * they are changed, so that what a child has done today stays.
 */
class SaveRoutine
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $children
     * @param  list<array<string, mixed>>  $steps
     */
    public function handle(Routine $routine, array $attributes, array $children, array $steps): Routine
    {
        return DB::transaction(function () use ($routine, $attributes, $children, $steps): Routine {
            $routine->fill($attributes)->save();
            $routine->children()->sync($children);

            $kept = [];

            foreach ($steps as $index => $data) {
                $id = $data['id'] ?? null;
                unset($data['id']);

                $step = $id === null ? null : $routine->steps()->whereKey($id)->first();
                $step ??= new RoutineStep;
                $step->fill([...$data, 'sort_order' => $index]);
                $routine->steps()->save($step);

                $kept[] = $step->id;
            }

            $routine->steps()->whereNotIn('id', $kept)->delete();

            return $routine;
        });
    }
}
