<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\ReminderSettingsRequest;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Minishlink\WebPush\MessageSentReport;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * An adult's own reminders: on this device, and quiet hours.
 */
class ReminderController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/Reminders', [
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'devices' => $user->pushSubscriptions()->count(),
            'settings' => [
                'remindersEnabled' => $user->reminders_enabled,
                'quietFrom' => $user->quiet_from === null ? null : substr($user->quiet_from, 0, 5),
                'quietUntil' => $user->quiet_until === null ? null : substr($user->quiet_until, 0, 5),
            ],
        ]);
    }

    public function update(ReminderSettingsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->forceFill($request->settings())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('reminders.edit');
    }

    /**
     * "Skicka en testnotis": sent at once to every device, also during quiet
     * hours, with the push service's answer when it fails.
     */
    public function test(Request $request, WebPushChannel $channel): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->pushSubscriptions()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Ingen enhet tar emot påminnelser än. Tryck Slå på på den här enheten först.']);

            return to_route('reminders.edit');
        }

        $reports = $channel->send($user, new ReminderNotification('test', 'Det fungerar', 'Så här ser en påminnelse ut.', route('reminders.edit')));
        $failed = array_filter($reports, fn (MessageSentReport $report) => ! $report->isSuccess());
        $sent = count($reports) - count($failed);

        if ($failed !== []) {
            $reason = Str::limit((string) reset($failed)->getReason(), 160);
            Log::warning('Testnotisen kunde inte skickas.', ['user_id' => $user->id, 'reason' => $reason]);
            Inertia::flash('toast', ['type' => 'error', 'message' => "Skickad till {$sent} av ".count($reports)." enheter. Push-tjänsten svarade: {$reason}"]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => $sent === 1 ? 'Testnotisen är skickad.' : "Testnotisen är skickad till {$sent} enheter."]);
        }

        return to_route('reminders.edit');
    }
}
