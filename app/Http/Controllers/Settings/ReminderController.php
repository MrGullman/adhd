<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\ReminderSettingsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
