<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\User;
use App\Reminders\SendReminders;
use App\Support\CurrentFamily;
use App\Support\LocalTime;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Runs every minute (routes/console.php). Only families where someone has a
 * device for push notifications are looked at.
 */
#[Signature('reminders:send')]
#[Description('Skickar påminnelser som push-notiser')]
class SendRemindersCommand extends Command
{
    public function handle(SendReminders $send, CurrentFamily $current): int
    {
        $now = LocalTime::now();
        $sent = 0;

        $withDevices = DB::table('family_user')
            ->whereIn('user_id', DB::table('push_subscriptions')->where('subscribable_type', (new User)->getMorphClass())->select('subscribable_id'))
            ->select('family_id');

        try {
            Family::query()->whereNull('suspended_at')->whereIn('id', $withDevices)->lazyById()->each(function (Family $family) use ($send, $current, $now, &$sent): void {
                $current->set($family);
                $sent += $send->handle($family, $now);
            });
        } finally {
            $current->forget();
        }

        // What was sent a month ago is no longer needed to stop repeats.
        DB::table('sent_reminders')->where('sent_at', '<', now()->subDays(30))->delete();

        $this->info("{$sent} påminnelser skickade.");

        return self::SUCCESS;
    }
}
