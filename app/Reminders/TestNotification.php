<?php

namespace App\Reminders;

use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * "Skicka en testnotis": straight away, to every device where someone has
 * turned reminders on, and says in a toast how it went. Quiet hours and the
 * reminders setting do not stop a test.
 */
class TestNotification
{
    public function __construct(private WebPushChannel $channel) {}

    /**
     * @param  string  $whose  For the toast, e.g. "Alvas" or "" for one's own.
     */
    public function send(User $user, string $url, string $whose = ''): void
    {
        $devices = $whose === '' ? 'Ingen enhet' : "Ingen av {$whose} enheter";

        if (! $user->pushSubscriptions()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => "{$devices} tar emot påminnelser än. Tryck Slå på påminnelser på enheten först."]);

            return;
        }

        $reports = $this->channel->send($user, new ReminderNotification('test', 'Det fungerar', 'Så här ser en påminnelse ut.', $url));
        $failed = array_filter($reports, fn (MessageSentReport $report) => ! $report->isSuccess());
        $sent = count($reports) - count($failed);

        if ($failed !== []) {
            $reason = Str::limit((string) reset($failed)->getReason(), 160);
            Log::warning('Testnotisen kunde inte skickas.', ['user_id' => $user->id, 'reason' => $reason]);
            Inertia::flash('toast', ['type' => 'error', 'message' => "Skickad till {$sent} av ".count($reports)." enheter. Push-tjänsten svarade: {$reason}"]);

            return;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $sent === 1 ? 'Testnotisen är skickad.' : "Testnotisen är skickad till {$sent} enheter."]);
    }
}
