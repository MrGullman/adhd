<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reminders\ChildReminderSettingsRequest;
use App\Http\Requests\Reminders\ReminderSettingsRequest;
use App\Models\Membership;
use App\Models\User;
use App\Reminders\TestNotification;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * An adult's own reminders: on this device, quiet hours, and the children's
 * activity reminders in the current family.
 */
class ReminderController extends Controller
{
    public function edit(Request $request, CurrentFamily $current): Response
    {
        /** @var User $user */
        $user = $request->user();
        $family = $current->getOrFail();
        $membership = Membership::query()->where('family_id', $family->id)->where('user_id', $user->id)->first();

        return Inertia::render('settings/Reminders', [
            'vapidPublicKey' => config('webpush.vapid.public_key'),
            'devices' => $user->pushSubscriptions()->count(),
            'settings' => [
                'remindersEnabled' => $user->reminders_enabled,
                'quietFrom' => $user->quiet_from === null ? null : substr($user->quiet_from, 0, 5),
                'quietUntil' => $user->quiet_until === null ? null : substr($user->quiet_until, 0, 5),
            ],
            // The children's activity reminders, in this family.
            'childReminders' => [
                'enabled' => (bool) $membership?->child_reminders,
                'childIds' => $membership?->child_reminder_ids,
            ],
            'children' => $family->children()->with('picture')->orderBy('name')->get()->map(fn (User $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
            ])->values(),
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
     * The children's activity reminders too, for every child or some.
     */
    public function children(ChildReminderSettingsRequest $request, CurrentFamily $current): RedirectResponse
    {
        $membership = Membership::query()
            ->where('family_id', $current->getOrFail()->id)
            ->where('user_id', $request->user()?->id)
            ->firstOrFail();
        $membership->forceFill($request->settings())->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('reminders.edit');
    }

    /**
     * "Skicka en testnotis": sent at once to every device, also during quiet
     * hours, with the push service's answer when it fails.
     */
    public function test(Request $request, TestNotification $test): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $test->send($user, route('reminders.edit'));

        return to_route('reminders.edit');
    }
}
