<?php

namespace App\Http\Controllers\Family;

use App\Display\DisplayContext;
use App\Display\DisplayView;
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

    public function update(UpdateDisplayViewRequest $request, CurrentFamily $current, Device $device): RedirectResponse
    {
        $family = $current->getOrFail();

        match ($request->string('mode')->toString()) {
            'member' => DisplayView::showMember($device, $family->members()->findOrFail($request->integer('user_id')), $request->minutes()),
            'off' => DisplayView::turnOff($device, $family->dayParts()),
            default => DisplayView::family($device),
        };

        Inertia::flash('toast', ['type' => 'success', 'message' => match ($request->string('mode')->toString()) {
            'member' => "Visas på {$device->name}.",
            'off' => "{$device->name} är släckt.",
            default => "{$device->name} visar familjen.",
        }]);

        return to_route('displays.control');
    }
}
