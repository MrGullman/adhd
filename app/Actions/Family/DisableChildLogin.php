<?php

namespace App\Actions\Family;

use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Stop a child from logging in, for example when a parent suspects that someone
 * else has got hold of the child's code or device.
 */
class DisableChildLogin
{
    public function handle(User $child): void
    {
        DB::transaction(function () use ($child): void {
            // A new remember token makes every "remember me" cookie for the child invalid.
            $child->forceFill([
                'login_disabled_at' => now(),
                'failed_pin_attempts' => 0,
                'remember_token' => Str::random(60),
            ])->save();

            Device::query()
                ->where('type', DeviceType::Child)
                ->where('user_id', $child->id)
                ->delete();

            if (config('session.driver') === 'database') {
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $child->id)
                    ->delete();
            }
        });
    }
}
