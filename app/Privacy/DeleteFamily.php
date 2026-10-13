<?php

namespace App\Privacy;

use App\Enums\FamilyRole;
use App\Models\Family;
use App\Models\Picture;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes a family and everything in it (docs/ARCHITECTURE.md, Integritet
 * och datakontroll): every row, every file, the children's accounts and the
 * accounts of adults who are in no other family. Admin accounts are kept.
 */
class DeleteFamily
{
    public function handle(Family $family): void
    {
        $members = DB::table('family_user')->where('family_id', $family->id)->get(['user_id', 'role']);

        // Children, and adults with no other family, go with the family.
        $leaving = $members
            ->filter(fn (object $member) => $member->role === FamilyRole::Child->value
                || ! DB::table('family_user')->where('user_id', $member->user_id)->where('family_id', '!=', $family->id)->exists())
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $leaving = User::query()->whereIn('id', $leaving)->where('is_admin', false)->pluck('id')->all();

        $this->deleteFiles($family);

        DB::transaction(function () use ($family, $leaving): void {
            // Rows with family_id go with the family (cascade).
            $family->delete();

            DB::table('push_subscriptions')->where('subscribable_type', (new User)->getMorphClass())->whereIn('subscribable_id', $leaving)->delete();
            DB::table('sessions')->whereIn('user_id', $leaving)->delete();
            DB::table('password_reset_tokens')->whereIn('email', User::query()->whereIn('id', $leaving)->whereNotNull('email')->pluck('email'))->delete();
            User::query()->whereIn('id', $leaving)->delete();
        });
    }

    /**
     * The family's pictures, uploads not yet processed, and exports.
     */
    private function deleteFiles(Family $family): void
    {
        $disk = Storage::disk(Picture::DISK);

        foreach (DB::table('pictures')->where('family_id', $family->id)->pluck('path') as $path) {
            $disk->delete((string) $path);
        }

        $disk->deleteDirectory("pictures/familjer/{$family->id}");
        $disk->deleteDirectory("exports/{$family->id}");
    }
}
