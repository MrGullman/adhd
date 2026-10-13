<?php

namespace App\Privacy;

use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Removes a child's account from a family. A child only in this family is
 * deleted with everything about them. A child who also lives in another
 * family keeps the account there; only this family's data about them goes.
 */
class RemoveChild
{
    public function handle(Family $family, User $child): void
    {
        $elsewhere = DB::table('family_user')->where('user_id', $child->id)->where('family_id', '!=', $family->id)->exists();

        DB::transaction(function () use ($family, $child, $elsewhere): void {
            if ($elsewhere) {
                $this->forgetInFamily($family, $child);

                return;
            }

            // The child's own photo, if the family uploaded it.
            $picture = $child->picture_id === null ? null : Picture::query()->where('family_id', $family->id)->find($child->picture_id);

            DB::table('push_subscriptions')->where('subscribable_type', $child->getMorphClass())->where('subscribable_id', $child->id)->delete();
            DB::table('sessions')->where('user_id', $child->id)->delete();
            $child->delete();
            $picture?->delete();
        });
    }

    /**
     * Only this family's data about the child.
     */
    private function forgetInFamily(Family $family, User $child): void
    {
        $inFamily = fn (string $table) => DB::table($table)->where('family_id', $family->id)->where('user_id', $child->id);
        $activities = DB::table('activities')->where('family_id', $family->id)->select('id');
        $routines = DB::table('routines')->where('family_id', $family->id)->select('id');

        DB::table('tasks')->where('family_id', $family->id)->where('assigned_to', $child->id)->delete();
        DB::table('recurring_tasks')->where('family_id', $family->id)->where('assigned_to', $child->id)->delete();
        DB::table('routine_child')->whereIn('routine_id', $routines)->where('user_id', $child->id)->delete();
        $inFamily('routine_runs')->delete();
        DB::table('activity_participants')->whereIn('activity_id', $activities)->where('user_id', $child->id)->delete();
        DB::table('activity_item_checks')->whereIn('activity_id', $activities)->where('user_id', $child->id)->delete();
        DB::table('activity_confirmations')->whereIn('activity_id', $activities)->where('user_id', $child->id)->delete();
        DB::table('journal_entry_child')->whereIn('journal_entry_id', DB::table('journal_entries')->where('family_id', $family->id)->select('id'))->where('user_id', $child->id)->delete();
        $inFamily('point_transactions')->delete();
        $inFamily('reward_redemptions')->delete();
        $inFamily('login_events')->delete();
        $inFamily('devices')->delete();
        DB::table('calendars')->where('family_id', $family->id)->where('user_id', $child->id)->update(['user_id' => null]);
        DB::table('family_user')->where('family_id', $family->id)->where('user_id', $child->id)->delete();
    }
}
