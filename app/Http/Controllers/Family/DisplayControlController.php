<?php

namespace App\Http\Controllers\Family;

use App\Display\DisplayContext;
use App\Display\DisplayView;
use App\Display\ScreenSchedule;
use App\Enums\DeviceType;
use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Display\UpdateDisplayViewRequest;
use App\Models\Device;
use App\Models\Membership;
use App\Models\User;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Hemskärmen from the phone: show a member's day on the display for a
 * while, go back to the family view, or turn the display off for the night.
 */
class DisplayControlController extends Controller
{
    public function index(Request $request, CurrentFamily $current): Response
    {
        $family = $current->getOrFail();
        $context = new DisplayContext($family);

        /** @var User $user */
        $user = $request->user();

        $displays = Device::query()
            ->where('type', DeviceType::Display)
            ->orderBy('name')
            ->get()
            ->filter(fn (Device $device) => $user->can('control', $device));

        return Inertia::render('display/Control', [
            'displays' => $displays->map(fn (Device $device) => [
                'id' => $device->id,
                'name' => $device->name,
                'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
                'view' => DisplayView::current($device, $context)->present(),
                'status' => $this->status($device, $context),
            ])->values(),
            'members' => $family->members()
                ->with('picture')
                ->orderBy('name')
                ->get()
                ->map(fn (User $member) => [
                    ...DisplayContext::member($member),
                    'isChild' => $member->getRelation('pivot') instanceof Membership && $member->getRelation('pivot')->role === FamilyRole::Child,
                ])
                ->sortByDesc('isChild')
                ->values(),
            'durations' => DisplayView::DURATIONS,
            'canSetUp' => $user->can('manageMembers', $family),
        ]);
    }

    /**
     * "Ladda om skärmen": the display's browser loads everything again within seconds.
     */
    public function reload(Request $request, Device $device): RedirectResponse
    {
        Gate::authorize('control', $device);

        $device->forceFill(['settings' => [...($device->settings ?? []), 'reload_at' => now()->toIso8601String()]])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$device->name} laddas om."]);

        return to_route('displays.control');
    }

    /**
     * What the screen does now, and when the browser and the Raspberry Pi last were in touch.
     *
     * @return array{on: bool, nextOn: string, browserSeenAt: string|null, piSeenAt: string|null, piPower: string|null, piUptime: int|null, schedule: string}
     */
    private function status(Device $device, DisplayContext $context): array
    {
        $pi = (array) ($device->settings['pi'] ?? []);
        $screen = ScreenSchedule::settings($device);

        return [
            'on' => ScreenSchedule::isOn($device, DisplayView::current($device, $context), $context->now),
            'nextOn' => ScreenSchedule::nextOn($device, $context->now)->format('H:i'),
            'browserSeenAt' => $device->last_seen_at?->toIso8601String(),
            'piSeenAt' => is_string($pi['seen_at'] ?? null) ? $pi['seen_at'] : null,
            'piPower' => is_string($pi['power'] ?? null) ? $pi['power'] : null,
            'piUptime' => is_int($pi['uptime'] ?? null) ? $pi['uptime'] : null,
            'schedule' => "Vardagar {$screen['weekday']['on']}–{$screen['weekday']['off']}, helger {$screen['weekend']['on']}–{$screen['weekend']['off']}",
        ];
    }

    public function update(UpdateDisplayViewRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $family = $current->getOrFail();

        match ($request->string('mode')->toString()) {
            'member' => DisplayView::showMember($device, $family->members()->findOrFail($request->integer('user_id')), $request->minutes()),
            'off' => DisplayView::turnOff($device),
            default => DisplayView::family($device),
        };

        // "Tänd skärmen" at night, when the schedule has it off: on for an hour.
        if ($request->string('mode')->toString() === 'family' && ! ScreenSchedule::scheduledOn($device, now()->toImmutable())) {
            ScreenSchedule::wakeUp($device);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($request->string('mode')->toString()) {
            'member' => "Visas på {$device->name}.",
            'off' => "{$device->name} är släckt.",
            default => "{$device->name} visar familjen.",
        }]);

        return to_route('displays.control');
    }
}
