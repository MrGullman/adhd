<?php

namespace App\Http\Controllers\Family;

use App\Enums\AvatarColor;
use App\Enums\FamilyRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Family\StoreChildRequest;
use App\Http\Requests\Family\UpdateChildRequest;
use App\Http\Requests\Privacy\RemoveChildRequest;
use App\Models\Device;
use App\Models\LoginEvent;
use App\Models\Membership;
use App\Models\User;
use App\Points\Points;
use App\Privacy\RemoveChild;
use App\Support\ChildPins;
use App\Support\CurrentFamily;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request, CurrentFamily $current): RedirectResponse
    {
        $family = $current->getOrFail();
        $pin = $request->validated('pin') ?: ChildPins::generate();

        $child = DB::transaction(function () use ($request, $family, $pin): User {
            $child = new User([
                'name' => $request->validated('name'),
                'avatar_color' => $request->enum('avatar_color', AvatarColor::class),
            ]);
            $child->pin = $pin;
            $child->save();

            $family->addMember($child, FamilyRole::Child);

            return $child;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$child->name} är tillagd."]);

        return to_route('children.show', $child->id);
    }

    public function show(CurrentFamily $current, int $child): Response
    {
        $family = $current->getOrFail();
        Gate::authorize('manageMembers', $family);

        $child = $family->findChildOrFail($child);

        return Inertia::render('family/Child', [
            'child' => [
                'id' => $child->id,
                'name' => $child->name,
                'avatarColor' => $child->avatar_color->value,
                'avatarUrl' => $child->avatarUrl(),
                'picture' => $child->picture?->toPicker(),
                // Parents may see the code so that they can tell the child.
                'pin' => $child->pin,
                'loginEnabled' => $child->loginEnabled(),
                'failedPinAttempts' => $child->failed_pin_attempts,
                'view' => [
                    'childView' => $child->child_view->value,
                    'childPictures' => $child->child_pictures,
                    'rewardsEnabled' => (bool) Membership::query()
                        ->where('family_id', $family->id)
                        ->where('user_id', $child->id)
                        ->value('rewards_enabled'),
                    'pointsEnabled' => Points::settings($family)['enabled'],
                ],
                'reminders' => [
                    'remindersEnabled' => $child->reminders_enabled,
                    'quietFrom' => $child->quiet_from === null ? null : substr($child->quiet_from, 0, 5),
                    'quietUntil' => $child->quiet_until === null ? null : substr($child->quiet_until, 0, 5),
                ],
            ],
            'devices' => Device::query()
                ->where('user_id', $child->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Device $device) => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'lastSeenAt' => $device->last_seen_at?->toIso8601String(),
                ]),
            'loginEvents' => LoginEvent::query()
                ->with('device')
                ->where('user_id', $child->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get()
                ->map(fn (LoginEvent $event) => [
                    'id' => $event->id,
                    'succeeded' => $event->succeeded,
                    'deviceName' => $event->device?->name,
                    'createdAt' => $event->created_at->toIso8601String(),
                ]),
            'avatarColors' => collect(AvatarColor::cases())
                ->map(fn (AvatarColor $color) => ['value' => $color->value, 'label' => $color->label()]),
        ]);
    }

    public function update(UpdateChildRequest $request, CurrentFamily $current, int $child): RedirectResponse
    {
        $child = $current->getOrFail()->findChildOrFail($child);

        $child->update([
            'name' => $request->validated('name'),
            'avatar_color' => $request->enum('avatar_color', AvatarColor::class),
        ]);
        $child->forceFill(['picture_id' => $request->validated('picture_id')])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sparat.']);

        return to_route('children.show', $child->id);
    }

    /**
     * Removes the child's account and everything about the child.
     */
    public function destroy(RemoveChildRequest $request, CurrentFamily $current, RemoveChild $remove, int $child): RedirectResponse
    {
        $family = $current->getOrFail();
        $child = $family->findChildOrFail($child);
        $name = $child->name;

        $remove->handle($family, $child);

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$name} är borttagen."]);

        return to_route('family.show');
    }
}
