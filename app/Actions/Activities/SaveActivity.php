<?php

namespace App\Actions\Activities;

use App\Models\Activity;
use App\Models\ActivityItem;
use Illuminate\Support\Facades\DB;

/**
 * Saves an activity with who it concerns and its packing list. Things keep
 * their id when changed, so what a child has packed stays.
 */
class SaveActivity
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $participants
     * @param  list<array{id: int|null, label: string, picture_id: int|null}>  $items
     */
    public function handle(Activity $activity, array $attributes, array $participants, array $items): Activity
    {
        return DB::transaction(function () use ($activity, $attributes, $participants, $items): Activity {
            $activity->fill($attributes)->save();
            $activity->participants()->sync($participants);

            $kept = [];

            foreach ($items as $index => $data) {
                $item = $data['id'] === null ? null : $activity->items()->whereKey($data['id'])->first();
                $item ??= new ActivityItem;
                $item->fill(['label' => $data['label'], 'picture_id' => $data['picture_id'], 'sort_order' => $index]);
                $activity->items()->save($item);

                $kept[] = $item->id;
            }

            $activity->items()->whereNotIn('id', $kept)->delete();

            return $activity;
        });
    }
}
