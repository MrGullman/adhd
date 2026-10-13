<?php

namespace App\Reminders;

use App\Models\Family;
use App\Notifications\ReminderNotification;
use App\Support\LocalTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Sends the current family's due reminders, each one once. Nothing is sent
 * during someone's quiet hours, when they have turned reminders off, or to
 * someone without a device that takes push notifications.
 */
class SendReminders
{
    public function __construct(private ReminderPlanner $planner) {}

    /**
     * @return int How many were sent.
     */
    public function handle(Family $family, CarbonImmutable $now): int
    {
        $sent = 0;

        foreach ($this->planner->due($family, $now) as $reminder) {
            $user = $reminder->user;

            if (! $user->reminders_enabled
                || $user->isSuspended()
                || $user->isQuietAt($reminder->at->setTimezone(LocalTime::ZONE))
                || ! $user->pushSubscriptions()->exists()) {
                continue;
            }

            // The unique key makes sure two runs never send the same reminder.
            $claimed = DB::table('sent_reminders')->insertOrIgnore([
                'user_id' => $user->id,
                'key' => $reminder->key,
                'sent_at' => now(),
            ]);

            if ($claimed === 0) {
                continue;
            }

            $user->notify(new ReminderNotification($reminder->key, $reminder->title, $reminder->body, $reminder->url));
            $sent++;
        }

        return $sent;
    }
}
