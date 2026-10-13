<?php

namespace App\Admin;

use App\Models\AdminAction;
use App\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Admins turn an adult's account or a whole family off when it is misused,
 * and on again. Nothing is removed, and every change is logged.
 */
class Suspensions
{
    public function suspendUser(User $user, string $reason, User $admin): void
    {
        if ($user->is_admin) {
            throw new InvalidArgumentException('Admin accounts cannot be suspended.');
        }

        DB::transaction(function () use ($user, $reason, $admin): void {
            $user->forceFill(['suspended_at' => now(), 'suspended_reason' => $reason])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->log($admin, 'suspended', 'user', $user->id, $this->userName($user), $reason);
        });
    }

    public function unsuspendUser(User $user, User $admin): void
    {
        DB::transaction(function () use ($user, $admin): void {
            $user->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
            $this->log($admin, 'unsuspended', 'user', $user->id, $this->userName($user), null);
        });
    }

    public function suspendFamily(Family $family, string $reason, User $admin): void
    {
        DB::transaction(function () use ($family, $reason, $admin): void {
            $family->forceFill(['suspended_at' => now(), 'suspended_reason' => $reason])->save();

            // Logged out right away. Adults in another family can log in there.
            $members = DB::table('family_user')->where('family_id', $family->id)->pluck('user_id');
            DB::table('sessions')->whereIn('user_id', User::query()->whereIn('id', $members)->where('is_admin', false)->select('id'))->delete();

            $this->log($admin, 'suspended', 'family', $family->id, $family->name, $reason);
        });
    }

    public function unsuspendFamily(Family $family, User $admin): void
    {
        DB::transaction(function () use ($family, $admin): void {
            $family->forceFill(['suspended_at' => null, 'suspended_reason' => null])->save();
            $this->log($admin, 'unsuspended', 'family', $family->id, $family->name, null);
        });
    }

    private function userName(User $user): string
    {
        return $user->email === null ? $user->name : "{$user->name} ({$user->email})";
    }

    private function log(User $admin, string $action, string $type, int $id, string $name, ?string $reason): void
    {
        $entry = new AdminAction;
        $entry->forceFill([
            'admin_id' => $admin->id,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'subject_name' => $name,
            'reason' => $reason,
        ])->save();
    }
}
